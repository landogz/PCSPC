<?php

namespace App\Repositories\Employees;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EmployeeRepository
{
    /**
     * @param  array{search?: string, status?: string, department?: string}  $filters
     */
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Employee::query()
            ->with(['department:id,uuid,code,name', 'user:id,uuid,name,email,employee_number,is_active'])
            ->latest('id');

        $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    public function findByUuid(string $uuid): ?Employee
    {
        return Employee::query()
            ->with(['department', 'user.roles'])
            ->where('uuid', $uuid)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Employee
    {
        return Employee::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Employee $employee, array $data): Employee
    {
        $employee->fill($data);
        $employee->save();

        return $employee->fresh(['department', 'user.roles']);
    }

    public function delete(Employee $employee): void
    {
        $employee->delete();
    }

    public function findUserByEmployeeNumberOrEmail(?string $employeeNumber, ?string $email): ?User
    {
        if (! filled($employeeNumber) && ! filled($email)) {
            return null;
        }

        return User::query()
            ->where(function (Builder $query) use ($employeeNumber, $email): void {
                if (filled($employeeNumber)) {
                    $query->orWhere('employee_number', $employeeNumber);
                }
                if (filled($email)) {
                    $query->orWhere('email', $email);
                }
            })
            ->first();
    }

    public function findEmployeeRole(): ?Role
    {
        return Role::query()->where('slug', 'employee')->first();
    }

    public function departmentIdByUuid(?string $uuid): ?int
    {
        if (! filled($uuid)) {
            return null;
        }

        return Department::query()->where('uuid', $uuid)->value('id');
    }

    /**
     * @return Collection<int, Department>
     */
    public function activeDepartments(): Collection
    {
        return Department::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'uuid', 'code', 'name']);
    }

    /**
     * @param  Builder<Employee>  $query
     * @param  array{search?: string, status?: string, department?: string}  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $inner) use ($search): void {
                $inner->where('employee_number', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('position_title', 'like', "%{$search}%");
            });
        }

        $status = trim((string) ($filters['status'] ?? ''));
        if ($status !== '') {
            $query->where('employment_status', $status);
        }

        $department = trim((string) ($filters['department'] ?? ''));
        if ($department !== '') {
            $query->whereHas('department', fn (Builder $inner) => $inner->where('uuid', $department));
        }
    }
}
