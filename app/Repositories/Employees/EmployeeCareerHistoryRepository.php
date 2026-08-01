<?php

namespace App\Repositories\Employees;

use App\Models\Employee;
use App\Models\EmployeeCareerHistory;
use Illuminate\Support\Collection;

class EmployeeCareerHistoryRepository
{
    /**
     * @return Collection<int, EmployeeCareerHistory>
     */
    public function listForEmployee(Employee $employee): Collection
    {
        return EmployeeCareerHistory::query()
            ->where('employee_id', $employee->id)
            ->orderByDesc('is_current')
            ->orderByDesc('effective_from')
            ->get();
    }

    public function findForEmployee(Employee $employee, string $uuid): ?EmployeeCareerHistory
    {
        return EmployeeCareerHistory::query()
            ->where('employee_id', $employee->id)
            ->where('uuid', $uuid)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Employee $employee, array $data): EmployeeCareerHistory
    {
        $data['employee_id'] = $employee->id;

        return EmployeeCareerHistory::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(EmployeeCareerHistory $history, array $data): EmployeeCareerHistory
    {
        $history->fill($data);
        $history->save();

        return $history->fresh();
    }

    public function delete(EmployeeCareerHistory $history): void
    {
        $history->delete();
    }

    public function clearCurrentFlags(Employee $employee, ?int $exceptId = null): void
    {
        $query = EmployeeCareerHistory::query()->where('employee_id', $employee->id);
        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }
        $query->update(['is_current' => false]);
    }
}
