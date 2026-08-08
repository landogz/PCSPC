<?php

namespace App\Repositories\Overtime;

use App\Models\OvertimeRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class OvertimeRequestRepository
{
    /**
     * @param  array{search?: string, status?: string, kind?: string, employee_id?: int|null}  $filters
     */
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = OvertimeRequest::query()
            ->with(['employee.department', 'submitter', 'workflowInstance.definition.steps', 'workflowInstance.actions.actor'])
            ->join('employees', 'employees.id', '=', 'overtime_requests.employee_id')
            ->select('overtime_requests.*')
            ->orderByDesc('overtime_requests.created_at');

        $employeeId = $filters['employee_id'] ?? null;
        if ($employeeId !== null) {
            $query->where('overtime_requests.employee_id', (int) $employeeId);
        }

        $status = trim((string) ($filters['status'] ?? ''));
        if ($status !== '') {
            $query->where('overtime_requests.status', $status);
        }

        $kind = trim((string) ($filters['kind'] ?? ''));
        if ($kind !== '') {
            $query->where('overtime_requests.kind', $kind);
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $inner) use ($search): void {
                $inner->where('employees.first_name', 'like', "%{$search}%")
                    ->orWhere('employees.last_name', 'like', "%{$search}%")
                    ->orWhere('employees.employee_number', 'like', "%{$search}%")
                    ->orWhere('overtime_requests.reason', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    public function findByUuid(string $uuid): ?OvertimeRequest
    {
        return OvertimeRequest::query()
            ->with(['employee.department', 'submitter', 'workflowInstance.definition.steps', 'workflowInstance.actions.actor'])
            ->where('uuid', $uuid)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): OvertimeRequest
    {
        return OvertimeRequest::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(OvertimeRequest $request, array $data): OvertimeRequest
    {
        $request->fill($data);
        $request->save();

        return $request->fresh([
            'employee.department',
            'submitter',
            'workflowInstance.definition.steps',
            'workflowInstance.actions.actor',
        ]);
    }

    public function hasPendingOrApprovedOnDate(int $employeeId, string $workDate, string $kind, ?int $ignoreId = null): bool
    {
        $query = OvertimeRequest::query()
            ->where('employee_id', $employeeId)
            ->whereDate('work_date', $workDate)
            ->where('kind', $kind)
            ->whereIn('status', ['pending', 'approved']);

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }
}
