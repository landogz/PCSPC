<?php

namespace App\Services\Overtime;

use App\Mail\Overtime\OvertimeRequestStatusMail;
use App\Models\OvertimeRequest;
use App\Models\User;
use App\Models\WorkflowInstance;
use App\Repositories\Overtime\OvertimeRequestRepository;
use App\Services\Audit\AuditLogger;
use App\Services\Notifications\NotificationService;
use App\Services\Workflow\WorkflowService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

class OvertimeRequestService
{
    public function __construct(
        private readonly OvertimeRequestRepository $requests,
        private readonly WorkflowService $workflows,
        private readonly NotificationService $notifications,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array{search?: string, status?: string, kind?: string}  $filters
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
     * @param  array{search?: string, status?: string, kind?: string}  $filters
     */
    public function listForApproval(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        if (($filters['status'] ?? '') === '') {
            $filters['status'] = 'pending';
        }

        return $this->requests->paginate($filters, $perPage);
    }

    public function find(string $uuid): OvertimeRequest
    {
        $request = $this->requests->findByUuid($uuid);
        if ($request === null) {
            abort(404, 'Overtime request not found.');
        }

        return $request;
    }

    /**
     * @param  array{kind: string, work_date: string, hours: float|int|string, reason: string, meal_notes?: string|null}  $payload
     */
    public function submit(User $actor, array $payload): OvertimeRequest
    {
        $employee = $actor->employee;
        if ($employee === null) {
            abort(422, 'Your account is not linked to an employee record.');
        }

        if (! $employee->isActiveEmployment()) {
            throw new InvalidArgumentException('Only active employees can file overtime.');
        }

        $kind = (string) $payload['kind'];
        if (! in_array($kind, OvertimeRequest::KINDS, true)) {
            throw new InvalidArgumentException('Invalid overtime kind.');
        }

        $hours = round((float) $payload['hours'], 2);
        $min = (float) config('workflow.ot_min_hours', 0.25);
        $max = (float) config('workflow.ot_max_hours', 24);
        if ($hours < $min || $hours > $max) {
            throw new InvalidArgumentException("Hours must be between {$min} and {$max}.");
        }

        $reason = trim((string) $payload['reason']);
        if (strlen($reason) < 3) {
            throw new InvalidArgumentException('A reason of at least 3 characters is required.');
        }

        $mealNotes = isset($payload['meal_notes']) ? trim((string) $payload['meal_notes']) : null;
        if ($kind === 'ot_meal' && ($mealNotes === null || $mealNotes === '')) {
            throw new InvalidArgumentException('Meal notes are required for OT Meal filings.');
        }

        if ($this->requests->hasPendingOrApprovedOnDate($employee->id, $payload['work_date'], $kind)) {
            throw new InvalidArgumentException('You already have a pending or approved filing for this date and kind.');
        }

        $definitionCode = (string) config('workflow.default_overtime_code', 'overtime');

        $request = DB::transaction(function () use ($actor, $employee, $kind, $hours, $reason, $mealNotes, $payload, $definitionCode): OvertimeRequest {
            $created = $this->requests->create([
                'employee_id' => $employee->id,
                'submitted_by' => $actor->id,
                'kind' => $kind,
                'work_date' => $payload['work_date'],
                'hours' => $hours,
                'reason' => $reason,
                'status' => 'pending',
                'meal_notes' => $kind === 'ot_meal' ? $mealNotes : null,
            ]);

            $this->workflows->start($definitionCode, $created, $actor);

            return $this->find($created->uuid);
        });

        $this->audit->log('overtime.request.submitted', [
            'request_id' => $request->uuid,
            'kind' => $request->kind,
            'work_date' => $request->work_date?->toDateString(),
            'hours' => number_format((float) $request->hours, 2, '.', ''),
        ]);

        $this->notifySubmitted($request);

        return $request;
    }

    /**
     * @param  array{notes?: string|null}  $payload
     */
    public function approve(User $actor, string $uuid, array $payload = []): OvertimeRequest
    {
        $request = $this->find($uuid);
        if (! $request->isPending()) {
            throw new InvalidArgumentException('Only pending overtime requests can be approved.');
        }

        $instance = $this->requireInstance($request);

        $updated = DB::transaction(function () use ($actor, $request, $instance, $payload): OvertimeRequest {
            $instance = $this->workflows->approve($actor, $instance->uuid, $payload);

            if ($instance->status === 'approved') {
                return $this->requests->update($request, ['status' => 'approved']);
            }

            return $this->find($request->uuid);
        });

        if ($updated->status === 'approved') {
            $this->audit->log('overtime.request.approved', [
                'request_id' => $updated->uuid,
                'kind' => $updated->kind,
            ]);
            $this->notifyDecision($updated, 'approved');
        } else {
            $this->audit->log('overtime.request.step_approved', [
                'request_id' => $updated->uuid,
                'current_step' => $updated->workflowInstance?->current_step_order,
            ]);
            $this->notifyStepAdvanced($updated);
        }

        return $updated;
    }

    /**
     * @param  array{notes?: string|null}  $payload
     */
    public function reject(User $actor, string $uuid, array $payload = []): OvertimeRequest
    {
        $request = $this->find($uuid);
        if (! $request->isPending()) {
            throw new InvalidArgumentException('Only pending overtime requests can be rejected.');
        }

        $instance = $this->requireInstance($request);

        $updated = DB::transaction(function () use ($actor, $request, $instance, $payload): OvertimeRequest {
            $this->workflows->reject($actor, $instance->uuid, $payload);

            return $this->requests->update($request, ['status' => 'rejected']);
        });

        $this->audit->log('overtime.request.rejected', [
            'request_id' => $updated->uuid,
            'kind' => $updated->kind,
        ]);

        $this->notifyDecision($updated, 'rejected');

        return $updated;
    }

    public function cancel(User $actor, string $uuid): OvertimeRequest
    {
        $request = $this->find($uuid);
        if (! $request->isPending()) {
            throw new InvalidArgumentException('Only pending overtime requests can be cancelled.');
        }

        $isOwner = (int) $request->submitted_by === (int) $actor->id
            || (int) ($request->employee?->user_id ?? 0) === (int) $actor->id;
        if (! $isOwner && ! $actor->hasPermission('ot.manage')) {
            abort(403, 'You can only cancel your own overtime requests.');
        }

        $instance = $request->workflowInstance;

        $updated = DB::transaction(function () use ($actor, $request, $instance): OvertimeRequest {
            if ($instance !== null && $instance->isPending()) {
                $this->workflows->cancel($actor, $instance->uuid);
            }

            return $this->requests->update($request, ['status' => 'cancelled']);
        });

        $this->audit->log('overtime.request.cancelled', [
            'request_id' => $updated->uuid,
            'kind' => $updated->kind,
        ]);

        $this->notifyDecision($updated, 'cancelled');

        return $updated;
    }

    private function requireInstance(OvertimeRequest $request): WorkflowInstance
    {
        $instance = $request->workflowInstance;
        if ($instance === null) {
            throw new InvalidArgumentException('This overtime request has no workflow instance.');
        }

        return $instance;
    }

    private function notifySubmitted(OvertimeRequest $request): void
    {
        $request->loadMissing(['employee', 'workflowInstance.definition.steps']);
        $approvers = $this->workflows->usersForCurrentStep($request->workflowInstance);

        $kindLabel = $request->kind === 'ot_meal' ? 'OT Meal' : 'OT';
        $title = 'Overtime request pending approval';
        $body = sprintf(
            '%s filed %s · %s · %s hour(s).',
            $request->employee?->fullName() ?? 'An employee',
            $kindLabel,
            $request->work_date?->toDateString() ?? '',
            number_format((float) $request->hours, 2, '.', ''),
        );
        $actionUrl = url('/modules/overtime');
        $meta = [
            'request_id' => $request->uuid,
            'kind' => $request->kind,
            'step' => $request->workflowInstance?->current_step_order,
        ];

        foreach ($approvers as $user) {
            if ($request->submitted_by && (int) $user->id === (int) $request->submitted_by) {
                continue;
            }
            $this->sendDualChannel($user, $request, 'submitted', 'overtime.request.submitted', $title, $body, $actionUrl, $meta);
        }

        $employeeUser = $request->employee?->user;
        if ($employeeUser !== null) {
            $this->sendDualChannel(
                $employeeUser,
                $request,
                'submitted',
                'overtime.request.submitted',
                'Overtime request submitted',
                $body,
                $actionUrl,
                $meta,
            );
        }
    }

    private function notifyStepAdvanced(OvertimeRequest $request): void
    {
        $request->loadMissing(['employee', 'workflowInstance.definition.steps']);
        $approvers = $this->workflows->usersForCurrentStep($request->workflowInstance);
        $step = $request->workflowInstance?->currentStep();

        $kindLabel = $request->kind === 'ot_meal' ? 'OT Meal' : 'OT';
        $title = 'Overtime awaiting '.($step?->label ?? 'next approval');
        $body = sprintf(
            '%s · %s · %s hour(s) — step %s.',
            $request->employee?->fullName() ?? 'Employee',
            $kindLabel,
            number_format((float) $request->hours, 2, '.', ''),
            $step?->label ?? (string) $request->workflowInstance?->current_step_order,
        );
        $actionUrl = url('/modules/overtime');
        $meta = [
            'request_id' => $request->uuid,
            'kind' => $request->kind,
            'step' => $request->workflowInstance?->current_step_order,
        ];

        foreach ($approvers as $user) {
            $this->sendDualChannel($user, $request, 'step', 'overtime.request.step', $title, $body, $actionUrl, $meta);
        }
    }

    private function notifyDecision(OvertimeRequest $request, string $event): void
    {
        $employeeUser = $request->employee?->user;
        if ($employeeUser === null) {
            return;
        }

        $title = match ($event) {
            'approved' => 'Overtime request approved',
            'rejected' => 'Overtime request rejected',
            'cancelled' => 'Overtime request cancelled',
            default => 'Overtime request update',
        };

        $kindLabel = $request->kind === 'ot_meal' ? 'OT Meal' : 'OT';
        $body = sprintf(
            '%s · %s · %s hour(s)',
            $kindLabel,
            $request->work_date?->toDateString() ?? '',
            number_format((float) $request->hours, 2, '.', ''),
        );

        $this->sendDualChannel(
            $employeeUser,
            $request,
            $event,
            'overtime.request.'.$event,
            $title,
            $body,
            url('/modules/overtime'),
            [
                'request_id' => $request->uuid,
                'kind' => $request->kind,
                'status' => $request->status,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function sendDualChannel(
        User $user,
        OvertimeRequest $request,
        string $mailEvent,
        string $notificationType,
        string $title,
        string $body,
        string $actionUrl,
        array $meta,
    ): void {
        if (filled($user->email)) {
            try {
                Mail::to($user->email)->send(new OvertimeRequestStatusMail($request, $mailEvent));
            } catch (\Throwable $exception) {
                Log::warning('Failed to send overtime request email.', [
                    'user_id' => $user->uuid,
                    'request_id' => $request->uuid,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $this->notifications->notify($user, $notificationType, $title, $body, $actionUrl, $meta);
    }
}
