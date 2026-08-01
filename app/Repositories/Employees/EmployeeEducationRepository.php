<?php

namespace App\Repositories\Employees;

use App\Models\Employee;
use App\Models\EmployeeEducation;
use Illuminate\Support\Collection;

class EmployeeEducationRepository
{
    /**
     * @return Collection<int, EmployeeEducation>
     */
    public function listForEmployee(Employee $employee): Collection
    {
        return EmployeeEducation::query()
            ->where('employee_id', $employee->id)
            ->orderByDesc('is_highest')
            ->orderByDesc('year_ended')
            ->orderBy('institution')
            ->get();
    }

    public function findForEmployee(Employee $employee, string $uuid): ?EmployeeEducation
    {
        return EmployeeEducation::query()
            ->where('employee_id', $employee->id)
            ->where('uuid', $uuid)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Employee $employee, array $data): EmployeeEducation
    {
        $data['employee_id'] = $employee->id;

        return EmployeeEducation::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(EmployeeEducation $education, array $data): EmployeeEducation
    {
        $education->fill($data);
        $education->save();

        return $education->fresh();
    }

    public function delete(EmployeeEducation $education): void
    {
        $education->delete();
    }

    public function clearHighestFlags(Employee $employee, ?int $exceptId = null): void
    {
        $query = EmployeeEducation::query()->where('employee_id', $employee->id);
        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }
        $query->update(['is_highest' => false]);
    }
}
