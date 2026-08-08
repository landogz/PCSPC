<?php

namespace App\Services\Leave;

use App\Mail\Leave\LeaveRequestStatusMail;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Repositories\Leave\LeaveBalanceRepository;
use App\Repositories\Leave\LeaveRequestRepository;
use App\Repositories\Leave\LeaveTypeRepository;
use App\Services\Audit\AuditLogger;
use App\Services\Notifications\NotificationService;
use App\Services\Workflow\WorkflowService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

class LeaveRequestService
{
    public function __construct(
        private readonly LeaveRequestRepository $requests,
        private readonly LeaveTypeRepository $types,
        private readonly LeaveBalanceRepository $balances,
        private readonly LeaveBalanceService $balanceService,
        private readonly WorkflowService $workflows,
        private readonly NotificationService $notifications,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array{search?: string, status?: string, leave_type?: string}  $filters
     */
    public function listMine(User $user, array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $employee = $user->employee;
        if ($employee === null) {
            abort(422, 'Your account is not linked to an employee record.');
        }

        return $this->requests->paginate([
            ...$filters,
            'employee_id' => $employee->id,
        ], $perPage);
    }

    /**
     * @param  array{search?: string, status?: string, leave_type?: string}  $filters
     */
    public function listForApproval(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        if (($filters['status'] ?? '') === '') {
            $filters['status'] = 'pending';
        }

        return $this->requests->paginate($filters, $perPage);
    }

    public function find(string $uuid): LeaveRequest
    {
        $request = $this->requests->findByUuid($uuid);
        if ($request === null) {
            abort(404, 'Leave request not found.');
        }

        return $request;
    }

    /**
     * Inclusive calendar-day count (working-day / holiday math later).
     */
    public function countDays(string $startDate, string $endDate): float
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();
        if ($end->lt($start)) {
            throw new InvalidArgumentException('End date must be on or after the start date.');
        }

        return (float) ($start->diffInDays($end) + 1);
    }

    /**
     * @param  array{leave_type_id: string, start_date: string, end_date: string, reason: string, days?: float|int|string|null}  $payload
     */
    public function submit(User $actor, array $payload): LeaveRequest
    {
        $employee = $actor->employee;
        if ($employee === null) {
            abort(422, 'Your account is not linked to an employee record.');
        }

        if (! $employee->isActiveEmployment()) {
            throw new InvalidArgumentException('Only active employees can file leave.');
        }

        $leaveType = $this->types->findByUuid($payload['leave_type_id']);
        if ($leaveType === null || ! $leaveType->is_active) {
            abort(422, 'Leave type is not available for filing.');
        }

        $reason = trim((string) $payload['reason']);
        if ($leaveType->requires_reason && strlen($reason) < 3) {
            throw new InvalidArgumentException('A reason is required for this leave type.');
        }

        $days = isset($payload['days']) && $payload['days'] !== null && $payload['days'] !== ''
            ? round((float) $payload['days'], 2)
            : $this->countDays($payload['start_date'], $payload['end_date']);

        if ($days <= 0) {
            throw new InvalidArgumentException('Leave days must be greater than zero.');
        }

        if ($this->requests->hasOverlappingPendingOrApproved(
            $employee->id,
            $payload['start_date'],
            $payload['end_date'],
        )) {
            throw new InvalidArgumentException('You already have a pending or approved leave that overlaps these dates.');
        }

        $leaveYear = $this->balanceService->currentLeaveYear(Carbon::parse($payload['start_date']));
        $balance = $this->balances->findOrCreate($employee->id, $leaveType->id, $leaveYear);
        if ($balance->ending() < $days) {
            throw new InvalidArgumentException(
                'Insufficient leave balance. Available: '.number_format($balance->ending(), 2).' day(s).'
            );
        }

        $definitionCode = $leaveType->requires_hr
            ? (string) config('workflow.default_leave_hr_code', 'leave_hr')
            : (string) config('workflow.default_leave_code', 'leave');

        $request = DB::transaction(function () use ($actor, $employee, $leaveType, $days, $reason, $payload, $definitionCode): LeaveRequest {
            $created = $this->requests->create([
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'submitted_by' => $actor->id,
                'start_date' => $payload['start_date'],
                'end_date' => $payload['end_date'],
                'days' => $days,
                'reason' => $reason,
                'status' => 'pending',
            ]);

            $this->workflows->start($definitionCode, $created, $actor);

            return $this->find($created->uuid);
        });

        $this->audit->log('leave.request.submitted', [
            'request_id' => $request->uuid,
            'employee_id' => $employee->uuid,
            'leave_type' => $leaveType->code,
            'days' => $days,
            'start_date' => $request->start_date?->toDateString(),
            'end_date' => $request->end_date?->toDateString(),
            'workflow' => $definitionCode,
        ]);

        $this->notifySubmitted($request);

        return $request;
    }

    /**
     * @param  array{approver_notes?: string|null, notes?: string|null}  $payload
     */
    public function approve(User $actor, string $uuid, array $payload = []): LeaveRequest
    {
        $request = $this->find($uuid);
        if (! $request->isPending()) {
            throw new InvalidArgumentException('Only pending leave requests can be approved.');
        }

        $notes = $this->extractNotes($payload);
        $instance = $request->workflowInstance;

        // Legacy filings without a workflow instance: finalize in one step.
        if ($instance === null) {
            $this->assertLegacyCanDecide($actor, $request);

            return $this->finalizeApproval($actor, $request, $notes);
        }

        $updated = DB::transaction(function () use ($actor, $request, $instance, $notes): LeaveRequest {
            $instance = $this->workflows->approve($actor, $instance->uuid, ['notes' => $notes]);

            if ($instance->status === 'approved') {
                return $this->finalizeApproval($actor, $request, $notes, alreadyInTransaction: true);
            }

            return $this->find($request->uuid);
        });

        if ($updated->status === 'approved') {
            $this->notifyDecision($updated, 'approved');
        } else {
            $this->audit->log('leave.request.step_approved', [
                'request_id' => $updated->uuid,
                'current_step' => $updated->workflowInstance?->current_step_order,
            ]);
            $this->notifyStepAdvanced($updated);
        }

        return $updated;
    }

    /**
     * @param  array{approver_notes?: string|null, notes?: string|null}  $payload
     */
    public function reject(User $actor, string $uuid, array $payload = []): LeaveRequest
    {
        $request = $this->find($uuid);
        if (! $request->isPending()) {
            throw new InvalidArgumentException('Only pending leave requests can be rejected.');
        }

        $notes = $this->extractNotes($payload);
        $instance = $request->workflowInstance;

        if ($instance === null) {
            $this->assertLegacyCanDecide($actor, $request);
        }

        $updated = DB::transaction(function () use ($actor, $request, $instance, $notes): LeaveRequest {
            if ($instance !== null && $instance->isPending()) {
                $this->workflows->reject($actor, $instance->uuid, ['notes' => $notes]);
            }

            return $this->requests->update($request, [
                'status' => 'rejected',
                'approver_notes' => $notes,
                'decided_by' => $actor->id,
                'decided_at' => now(),
            ]);
        });

        $this->audit->log('leave.request.rejected', [
            'request_id' => $updated->uuid,
            'employee_id' => $updated->employee?->uuid,
            'leave_type' => $updated->leaveType?->code,
            'days' => (float) $updated->days,
        ]);

        $this->notifyDecision($updated, 'rejected');

        return $updated;
    }

    public function cancel(User $actor, string $uuid): LeaveRequest
    {
        $request = $this->find($uuid);
        $employee = $actor->employee;

        $owns = $employee !== null && (int) $request->employee_id === (int) $employee->id;
        $isAdmin = $actor->hasPermission('leave.manage');

        if (! $owns && ! $isAdmin) {
            abort(403, 'You do not have permission to cancel this leave request.');
        }

        if (! $request->isPending()) {
            throw new InvalidArgumentException('Only pending leave requests can be cancelled.');
        }

        $instance = $request->workflowInstance;

        $updated = DB::transaction(function () use ($actor, $request, $instance): LeaveRequest {
            if ($instance !== null && $instance->isPending()) {
                $this->workflows->cancel($actor, $instance->uuid);
            }

            return $this->requests->update($request, [
                'status' => 'cancelled',
                'decided_by' => $actor->id,
                'decided_at' => now(),
            ]);
        });

        $this->audit->log('leave.request.cancelled', [
            'request_id' => $updated->uuid,
            'employee_id' => $updated->employee?->uuid,
            'leave_type' => $updated->leaveType?->code,
        ]);

        $this->notifyDecision($updated, 'cancelled');

        return $updated;
    }

    /**
     * @param  array{approver_notes?: string|null, notes?: string|null}  $payload
     */
    private function extractNotes(array $payload): ?string
    {
        $notes = $payload['approver_notes'] ?? $payload['notes'] ?? null;
        if ($notes === null) {
            return null;
        }

        $trimmed = trim((string) $notes);

        return $trimmed !== '' ? $trimmed : null;
    }

    private function finalizeApproval(
        User $actor,
        LeaveRequest $request,
        ?string $notes,
        bool $alreadyInTransaction = false,
    ): LeaveRequest {
        $runner = function () use ($actor, $request, $notes): LeaveRequest {
            $leaveYear = $this->balanceService->currentLeaveYear($request->start_date);
            $balance = $this->balances->findOrCreate(
                $request->employee_id,
                $request->leave_type_id,
                $leaveYear,
            );

            $days = (float) $request->days;
            if ($balance->ending() < $days) {
                throw new InvalidArgumentException(
                    'Insufficient leave balance to approve. Available: '.number_format($balance->ending(), 2).' day(s).'
                );
            }

            $balance = $this->balances->applyUse($balance, $days);
            $entry = $this->balances->createLedgerEntry([
                'leave_balance_id' => $balance->id,
                'employee_id' => $request->employee_id,
                'leave_type_id' => $request->leave_type_id,
                'entry_type' => 'use',
                'amount' => $days,
                'effective_date' => $request->start_date?->toDateString(),
                'period_key' => 'req-'.$request->uuid,
                'reason' => 'Leave request approved',
                'meta' => [
                    'request_id' => $request->uuid,
                    'ending_after' => $balance->ending(),
                ],
                'created_by' => $actor->id,
            ]);

            $saved = $this->requests->update($request, [
                'status' => 'approved',
                'approver_notes' => $notes,
                'decided_by' => $actor->id,
                'decided_at' => now(),
                'leave_balance_id' => $balance->id,
            ]);

            $this->audit->log('leave.request.approved', [
                'request_id' => $saved->uuid,
                'employee_id' => $saved->employee?->uuid,
                'leave_type' => $saved->leaveType?->code,
                'days' => $days,
                'ledger_id' => $entry->uuid,
                'balance_id' => $balance->uuid,
            ]);

            return $saved;
        };

        $updated = $alreadyInTransaction ? $runner() : DB::transaction($runner);

        if (! $alreadyInTransaction) {
            $this->notifyDecision($updated, 'approved');
        }

        return $updated;
    }

    private function assertLegacyCanDecide(User $actor, LeaveRequest $request): void
    {
        $requiresHr = (bool) ($request->leaveType?->requires_hr);

        if ($requiresHr) {
            if (! $actor->hasPermission('leave.manage')) {
                abort(403, 'This leave type requires HR approval (leave.manage).');
            }

            return;
        }

        if (! $actor->hasPermission('leave.approve') && ! $actor->hasPermission('leave.manage')) {
            abort(403, 'You do not have permission to approve or reject leave.');
        }
    }

    private function notifySubmitted(LeaveRequest $request): void
    {
        $request->loadMissing(['employee', 'leaveType', 'workflowInstance.definition.steps']);
        $approvers = $request->workflowInstance
            ? $this->workflows->usersForCurrentStep($request->workflowInstance)
            : [];

        $title = 'Leave request pending approval';
        $body = sprintf(
            '%s filed %s (%s day(s)) from %s to %s.',
            $request->employee?->fullName() ?? 'An employee',
            $request->leaveType?->code ?? 'leave',
            number_format((float) $request->days, 2, '.', ''),
            $request->start_date?->toDateString() ?? '',
            $request->end_date?->toDateString() ?? '',
        );
        $actionUrl = url('/modules/leave');
        $meta = [
            'request_id' => $request->uuid,
            'leave_type' => $request->leaveType?->code,
            'step' => $request->workflowInstance?->current_step_order,
        ];

        foreach ($approvers as $user) {
            if ($request->submitted_by && (int) $user->id === (int) $request->submitted_by) {
                continue;
            }
            $this->sendDualChannel($user, $request, 'submitted', 'leave.request.submitted', $title, $body, $actionUrl, $meta);
        }

        $employeeUser = $request->employee?->user;
        if ($employeeUser !== null) {
            $this->sendDualChannel(
                $employeeUser,
                $request,
                'submitted',
                'leave.request.submitted',
                'Leave request submitted',
                $body,
                $actionUrl,
                $meta,
            );
        }
    }

    private function notifyStepAdvanced(LeaveRequest $request): void
    {
        $request->loadMissing(['employee', 'leaveType', 'workflowInstance.definition.steps']);
        $approvers = $request->workflowInstance
            ? $this->workflows->usersForCurrentStep($request->workflowInstance)
            : [];
        $step = $request->workflowInstance?->currentStep();

        $title = 'Leave awaiting '.($step?->label ?? 'next approval');
        $body = sprintf(
            '%s · %s day(s) · %s → %s — step %s.',
            $request->leaveType?->code ?? 'Leave',
            number_format((float) $request->days, 2, '.', ''),
            $request->start_date?->toDateString() ?? '',
            $request->end_date?->toDateString() ?? '',
            $step?->label ?? (string) $request->workflowInstance?->current_step_order,
        );
        $actionUrl = url('/modules/leave');
        $meta = [
            'request_id' => $request->uuid,
            'leave_type' => $request->leaveType?->code,
            'step' => $request->workflowInstance?->current_step_order,
        ];

        foreach ($approvers as $user) {
            $this->sendDualChannel($user, $request, 'step', 'leave.request.step', $title, $body, $actionUrl, $meta);
        }
    }

    private function notifyDecision(LeaveRequest $request, string $event): void
    {
        $employeeUser = $request->employee?->user;
        if ($employeeUser === null) {
            return;
        }

        $title = match ($event) {
            'approved' => 'Leave request approved',
            'rejected' => 'Leave request rejected',
            'cancelled' => 'Leave request cancelled',
            default => 'Leave request update',
        };

        $body = sprintf(
            '%s · %s day(s) · %s → %s',
            $request->leaveType?->code ?? 'Leave',
            number_format((float) $request->days, 2, '.', ''),
            $request->start_date?->toDateString() ?? '',
            $request->end_date?->toDateString() ?? '',
        );

        $this->sendDualChannel(
            $employeeUser,
            $request,
            $event,
            'leave.request.'.$event,
            $title,
            $body,
            url('/modules/leave'),
            [
                'request_id' => $request->uuid,
                'leave_type' => $request->leaveType?->code,
                'status' => $request->status,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function sendDualChannel(
        User $user,
        LeaveRequest $request,
        string $mailEvent,
        string $notificationType,
        string $title,
        string $body,
        string $actionUrl,
        array $meta,
    ): void {
        if (filled($user->email)) {
            try {
                Mail::to($user->email)->send(new LeaveRequestStatusMail($request, $mailEvent));
            } catch (\Throwable $exception) {
                Log::warning('Failed to send leave request email.', [
                    'user_id' => $user->uuid,
                    'request_id' => $request->uuid,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $this->notifications->notify($user, $notificationType, $title, $body, $actionUrl, $meta);
    }
}
