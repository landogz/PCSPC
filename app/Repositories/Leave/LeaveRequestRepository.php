<?php

namespace App\Repositories\Leave;

use App\Models\LeaveRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class LeaveRequestRepository
{
    /**
     * @param  array{search?: string, status?: string, leave_type?: string, employee_id?: int|null, scope?: string}  $filters
     */
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = LeaveRequest::query()
            ->with(['employee.department', 'leaveType', 'submitter', 'decider', 'workflowInstance.definition.steps', 'workflowInstance.actions.actor'])
            ->join('employees', 'employees.id', '=', 'leave_requests.employee_id')
            ->select('leave_requests.*')
            ->orderByDesc('leave_requests.created_at');

        $employeeId = $filters['employee_id'] ?? null;
        if ($employeeId !== null) {
            $query->where('leave_requests.employee_id', (int) $employeeId);
        }

        $status = trim((string) ($filters['status'] ?? ''));
        if ($status !== '') {
            $query->where('leave_requests.status', $status);
        }

        $type = trim((string) ($filters['leave_type'] ?? ''));
        if ($type !== '') {
            $query->whereHas('leaveType', function (Builder $q) use ($type): void {
                $q->where('uuid', $type)->orWhere('code', $type);
            });
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $inner) use ($search): void {
                $inner->where('employees.first_name', 'like', "%{$search}%")
                    ->orWhere('employees.last_name', 'like', "%{$search}%")
                    ->orWhere('employees.employee_number', 'like', "%{$search}%")
                    ->orWhere('leave_requests.reason', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    public function findByUuid(string $uuid): ?LeaveRequest
    {
        return LeaveRequest::query()
            ->with(['employee.department', 'leaveType', 'submitter', 'decider', 'leaveBalance', 'workflowInstance.definition.steps', 'workflowInstance.actions.actor'])
            ->where('uuid', $uuid)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): LeaveRequest
    {
        return LeaveRequest::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(LeaveRequest $request, array $data): LeaveRequest
    {
        $request->fill($data);
        $request->save();

        return $request->fresh([
            'employee.department',
            'leaveType',
            'submitter',
            'decider',
            'leaveBalance',
            'workflowInstance.definition.steps',
            'workflowInstance.actions.actor',
        ]);
    }

    public function hasOverlappingPendingOrApproved(
        int $employeeId,
        string $startDate,
        string $endDate,
        ?int $ignoreId = null,
    ): bool {
        $query = LeaveRequest::query()
            ->where('employee_id', $employeeId)
            ->whereIn('status', ['pending', 'approved'])
            ->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate);

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }
}
