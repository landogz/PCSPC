<?php

namespace App\Repositories\Employees;

use App\Models\Employee;
use App\Models\EmployeeEmploymentHistory;
use Illuminate\Support\Collection;

class EmployeeEmploymentHistoryRepository
{
    /**
     * @return Collection<int, EmployeeEmploymentHistory>
     */
    public function listForEmployee(Employee $employee): Collection
    {
        return EmployeeEmploymentHistory::query()
            ->where('employee_id', $employee->id)
            ->orderByDesc('is_current')
            ->orderByDesc('date_from')
            ->get();
    }

    public function findForEmployee(Employee $employee, string $uuid): ?EmployeeEmploymentHistory
    {
        return EmployeeEmploymentHistory::query()
            ->where('employee_id', $employee->id)
            ->where('uuid', $uuid)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Employee $employee, array $data): EmployeeEmploymentHistory
    {
        $data['employee_id'] = $employee->id;

        return EmployeeEmploymentHistory::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(EmployeeEmploymentHistory $history, array $data): EmployeeEmploymentHistory
    {
        $history->fill($data);
        $history->save();

        return $history->fresh();
    }

    public function delete(EmployeeEmploymentHistory $history): void
    {
        $history->delete();
    }

    public function clearCurrentFlags(Employee $employee, ?int $exceptId = null): void
    {
        $query = EmployeeEmploymentHistory::query()->where('employee_id', $employee->id);
        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }
        $query->update(['is_current' => false]);
    }
}
