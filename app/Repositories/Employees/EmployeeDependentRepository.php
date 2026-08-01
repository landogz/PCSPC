<?php

namespace App\Repositories\Employees;

use App\Models\Employee;
use App\Models\EmployeeDependent;
use Illuminate\Support\Collection;

class EmployeeDependentRepository
{
    /**
     * @return Collection<int, EmployeeDependent>
     */
    public function listForEmployee(Employee $employee): Collection
    {
        return EmployeeDependent::query()
            ->where('employee_id', $employee->id)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    public function findForEmployee(Employee $employee, string $uuid): ?EmployeeDependent
    {
        return EmployeeDependent::query()
            ->where('employee_id', $employee->id)
            ->where('uuid', $uuid)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Employee $employee, array $data): EmployeeDependent
    {
        $data['employee_id'] = $employee->id;

        return EmployeeDependent::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(EmployeeDependent $dependent, array $data): EmployeeDependent
    {
        $dependent->fill($data);
        $dependent->save();

        return $dependent->fresh();
    }

    public function delete(EmployeeDependent $dependent): void
    {
        $dependent->delete();
    }
}
