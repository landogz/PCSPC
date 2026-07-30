<?php

namespace App\Repositories\Security;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class UserRepository
{
    /**
     * @param  array{search?: string, status?: string, role?: string}  $filters
     */
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = User::query()
            ->with('roles')
            ->latest('id');

        $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    public function findByUuid(string $uuid): ?User
    {
        return User::query()
            ->with('roles.permissions')
            ->where('uuid', $uuid)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, array $roleIds = []): User
    {
        /** @var User $user */
        $user = User::query()->create($data);

        if ($roleIds !== []) {
            $user->roles()->sync($roleIds);
        }

        return $user->load('roles');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data, ?array $roleIds = null): User
    {
        $user->fill($data);
        $user->save();

        if ($roleIds !== null) {
            $user->roles()->sync($roleIds);
        }

        return $user->load('roles');
    }

    public function unlock(User $user): User
    {
        $user->forceFill([
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ])->save();

        return $user->fresh(['roles']);
    }

    public function delete(User $user): void
    {
        $user->roles()->detach();
        $user->tokens()->delete();
        $user->delete();
    }

    public function findEmployeeByUuid(string $uuid): ?Employee
    {
        return Employee::query()
            ->with('user:id,uuid,email,employee_number')
            ->where('uuid', $uuid)
            ->first();
    }

    /**
     * @return Collection<int, Employee>
     */
    public function searchEmployeesForAccount(string $search, int $limit = 15): Collection
    {
        $search = trim($search);
        $query = Employee::query()
            ->with('user:id,uuid,email,employee_number')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit($limit);

        if ($search !== '') {
            $query->where(function (Builder $inner) use ($search): void {
                $inner->where('employee_number', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('middle_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->get();
    }

    public function userExistsForEmployee(Employee $employee): bool
    {
        if ($employee->user_id) {
            return true;
        }

        if (! filled($employee->email) && ! filled($employee->employee_number)) {
            return false;
        }

        return User::query()
            ->where(function (Builder $query) use ($employee): void {
                if (filled($employee->email)) {
                    $query->orWhere('email', $employee->email);
                }
                if (filled($employee->employee_number)) {
                    $query->orWhere('employee_number', $employee->employee_number);
                }
            })
            ->exists();
    }

    public function linkEmployee(Employee $employee, User $user): void
    {
        $employee->forceFill(['user_id' => $user->id])->save();
    }

    /**
     * @return Collection<int, Role>
     */
    public function allRoles(): Collection
    {
        return Role::query()->orderBy('name')->get(['id', 'uuid', 'name', 'slug', 'requires_mfa']);
    }

    /**
     * @return list<int>
     */
    public function roleIdsByUuids(array $uuids): array
    {
        if ($uuids === []) {
            return [];
        }

        return Role::query()
            ->whereIn('uuid', $uuids)
            ->pluck('id')
            ->all();
    }

    /**
     * @param  Builder<User>  $query
     * @param  array{search?: string, status?: string, role?: string}  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $inner) use ($search): void {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('employee_number', 'like', "%{$search}%");
            });
        }

        $status = $filters['status'] ?? null;
        if ($status === 'active') {
            $query->where('is_active', true)->where(function (Builder $inner): void {
                $inner->whereNull('locked_until')->orWhere('locked_until', '<=', now());
            });
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        } elseif ($status === 'locked') {
            $query->whereNotNull('locked_until')->where('locked_until', '>', now());
        }

        $role = $filters['role'] ?? null;
        if (is_string($role) && $role !== '') {
            $query->whereHas('roles', fn (Builder $inner) => $inner->where('slug', $role));
        }
    }
}
