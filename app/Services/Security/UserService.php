<?php

namespace App\Services\Security;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Repositories\Security\UserRepository;
use App\Services\Administration\PasswordPolicyService;
use App\Services\Audit\AuditLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly AuditLogger $audit,
        private readonly PasswordPolicyService $passwordPolicy,
    ) {}

    /**
     * @param  array{search?: string, status?: string, role?: string}  $filters
     */
    public function list(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return $this->users->paginate($filters, $perPage);
    }

    public function find(string $uuid): User
    {
        $user = $this->users->findByUuid($uuid);

        if ($user === null) {
            abort(404, 'User not found.');
        }

        return $user;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function searchEmployees(string $search): array
    {
        return $this->users->searchEmployeesForAccount($search)->map(function (Employee $employee) {
            $hasAccount = $this->users->userExistsForEmployee($employee);

            return [
                'id' => $employee->uuid,
                'employee_number' => $employee->employee_number,
                'full_name' => $employee->fullName(),
                'email' => $employee->email,
                'photo_url' => $employee->photoUrl(),
                'has_account' => $hasAccount,
                'label' => trim($employee->employee_number.' — '.$employee->fullName()),
            ];
        })->values()->all();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload): User
    {
        $employee = $this->users->findEmployeeByUuid((string) $payload['employee_id']);
        if ($employee === null) {
            throw ValidationException::withMessages([
                'employee_id' => ['Selected employee was not found.'],
            ]);
        }

        if ($this->users->userExistsForEmployee($employee)) {
            throw ValidationException::withMessages([
                'employee_id' => ['This employee already has a user account.'],
            ]);
        }

        if (! filled($employee->email)) {
            throw ValidationException::withMessages([
                'employee_id' => ['Selected employee needs an email before a login can be created.'],
            ]);
        }

        $roleIds = $this->users->roleIdsByUuids($payload['role_ids'] ?? []);

        $user = DB::transaction(function () use ($payload, $employee, $roleIds): User {
            $forceChange = $this->passwordPolicy->current()['force_change_temporary'];

            $user = $this->users->create([
                'name' => $employee->fullName(),
                'email' => strtolower((string) $employee->email),
                'employee_number' => $employee->employee_number,
                'password' => $payload['password'],
                'is_active' => (bool) ($payload['is_active'] ?? true),
                'is_protected' => false,
                'mfa_enabled' => (bool) ($payload['mfa_enabled'] ?? false),
                'password_changed_at' => now(),
                'must_change_password' => $forceChange,
                'email_verified_at' => now(),
            ], $roleIds);

            $this->users->linkEmployee($employee, $user);

            return $user->fresh(['roles']);
        });

        $this->audit->log('user.created', [
            'user_id' => $user->uuid,
            'email' => $user->email,
            'employee_number' => $user->employee_number,
            'employee_id' => $employee->uuid,
            'roles' => $user->roles->pluck('slug')->values()->all(),
        ]);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(string $uuid, array $payload, ?User $actor = null): User
    {
        $user = $this->find($uuid);

        if ($user->isProtected() && array_key_exists('is_active', $payload) && ! $payload['is_active']) {
            throw ValidationException::withMessages([
                'is_active' => ['Protected accounts cannot be deactivated.'],
            ]);
        }

        if ($actor && $actor->id === $user->id && array_key_exists('is_active', $payload) && ! $payload['is_active']) {
            throw ValidationException::withMessages([
                'is_active' => ['You cannot deactivate your own account.'],
            ]);
        }

        $data = [
            'name' => $payload['name'],
            'email' => $payload['email'],
            'is_active' => (bool) ($payload['is_active'] ?? $user->is_active),
            'mfa_enabled' => (bool) ($payload['mfa_enabled'] ?? $user->mfa_enabled),
        ];

        $passwordChanged = ! empty($payload['password']);
        if ($passwordChanged) {
            $this->passwordPolicy->assertNotReused($user, (string) $payload['password']);
            $this->passwordPolicy->rememberCurrentPassword($user);
            $data['password'] = $payload['password'];
            $data['password_changed_at'] = now();
            $data['must_change_password'] = true;
        }

        $roleIds = array_key_exists('role_ids', $payload)
            ? $this->users->roleIdsByUuids($payload['role_ids'] ?? [])
            : null;

        if ($user->isProtected() && $roleIds !== null) {
            $this->assertProtectedKeepsSuperAdmin($roleIds);
        }

        $updated = $this->users->update($user, $data, $roleIds);

        $this->audit->log('user.updated', [
            'user_id' => $updated->uuid,
            'email' => $updated->email,
            'password_changed' => $passwordChanged,
            'is_active' => (bool) $updated->is_active,
            'roles' => $updated->roles->pluck('slug')->values()->all(),
        ], $actor);

        return $updated;
    }

    public function unlock(string $uuid): User
    {
        $user = $this->users->unlock($this->find($uuid));

        $this->audit->log('user.unlocked', [
            'user_id' => $user->uuid,
            'email' => $user->email,
        ]);

        return $user;
    }

    public function deactivate(string $uuid, ?User $actor = null): User
    {
        $user = $this->find($uuid);

        if ($user->isProtected()) {
            throw ValidationException::withMessages([
                'user' => ['Protected accounts cannot be deactivated or deleted.'],
            ]);
        }

        if ($actor && $actor->id === $user->id) {
            throw ValidationException::withMessages([
                'user' => ['You cannot deactivate your own account.'],
            ]);
        }

        $updated = $this->users->update($user, ['is_active' => false]);

        $this->audit->log('user.deactivated', [
            'user_id' => $updated->uuid,
            'email' => $updated->email,
        ], $actor);

        return $updated;
    }

    public function delete(string $uuid, ?User $actor = null): void
    {
        $user = $this->find($uuid);

        if ($user->isProtected()) {
            throw ValidationException::withMessages([
                'user' => ['Protected accounts cannot be deleted.'],
            ]);
        }

        if ($actor && $actor->id === $user->id) {
            throw ValidationException::withMessages([
                'user' => ['You cannot delete your own account.'],
            ]);
        }

        if ($user->employee()->exists()) {
            throw ValidationException::withMessages([
                'user' => ['Unlink or remove the employee 201 record before deleting this login.'],
            ]);
        }

        $meta = [
            'user_id' => $user->uuid,
            'email' => $user->email,
            'employee_number' => $user->employee_number,
        ];

        $this->users->delete($user);

        $this->audit->log('user.deleted', $meta, $actor);
    }

    /**
     * @return Collection<int, Role>
     */
    public function roles(): Collection
    {
        return $this->users->allRoles();
    }

    /**
     * @param  list<int>  $roleIds
     */
    private function assertProtectedKeepsSuperAdmin(array $roleIds): void
    {
        $hasSuperAdmin = Role::query()
            ->where('slug', 'super-admin')
            ->whereIn('id', $roleIds)
            ->exists();

        if (! $hasSuperAdmin) {
            throw ValidationException::withMessages([
                'role_ids' => ['Protected accounts must keep the Super Admin role.'],
            ]);
        }
    }
}
