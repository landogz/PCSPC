<?php

namespace App\Services\Security;

use App\Models\User;
use App\Repositories\Security\UserRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function __construct(
        private readonly UserRepository $users,
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
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload): User
    {
        $roleIds = $this->users->roleIdsByUuids($payload['role_ids'] ?? []);

        return $this->users->create([
            'name' => $payload['name'],
            'email' => $payload['email'],
            'employee_number' => $payload['employee_number'] ?? null,
            'password' => $payload['password'],
            'is_active' => (bool) ($payload['is_active'] ?? true),
            'mfa_enabled' => (bool) ($payload['mfa_enabled'] ?? false),
            'password_changed_at' => now(),
            'email_verified_at' => now(),
        ], $roleIds);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(string $uuid, array $payload, ?User $actor = null): User
    {
        $user = $this->find($uuid);

        if ($actor && $actor->id === $user->id && array_key_exists('is_active', $payload) && ! $payload['is_active']) {
            throw ValidationException::withMessages([
                'is_active' => ['You cannot deactivate your own account.'],
            ]);
        }

        $data = [
            'name' => $payload['name'],
            'email' => $payload['email'],
            'employee_number' => $payload['employee_number'] ?? null,
            'is_active' => (bool) ($payload['is_active'] ?? $user->is_active),
            'mfa_enabled' => (bool) ($payload['mfa_enabled'] ?? $user->mfa_enabled),
        ];

        if (! empty($payload['password'])) {
            $data['password'] = $payload['password'];
            $data['password_changed_at'] = now();
        }

        $roleIds = array_key_exists('role_ids', $payload)
            ? $this->users->roleIdsByUuids($payload['role_ids'] ?? [])
            : null;

        return $this->users->update($user, $data, $roleIds);
    }

    public function unlock(string $uuid): User
    {
        return $this->users->unlock($this->find($uuid));
    }

    public function deactivate(string $uuid, ?User $actor = null): User
    {
        $user = $this->find($uuid);

        if ($actor && $actor->id === $user->id) {
            throw ValidationException::withMessages([
                'user' => ['You cannot deactivate your own account.'],
            ]);
        }

        return $this->users->update($user, ['is_active' => false]);
    }

    /**
     * @return Collection<int, \App\Models\Role>
     */
    public function roles(): Collection
    {
        return $this->users->allRoles();
    }
}
