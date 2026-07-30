<?php

namespace App\Services\Security;

use App\Models\Role;
use App\Repositories\Security\RoleRepository;
use App\Services\Audit\AuditLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RoleService
{
    /** @var list<string> */
    public const SYSTEM_SLUGS = ['super-admin', 'hr-admin', 'employee'];

    public function __construct(
        private readonly RoleRepository $roles,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array{search?: string}  $filters
     */
    public function list(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return $this->roles->paginate($filters, $perPage);
    }

    public function find(string $uuid): Role
    {
        $role = $this->roles->findByUuid($uuid);

        if ($role === null) {
            abort(404, 'Role not found.');
        }

        return $role;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload): Role
    {
        $slug = $this->resolveSlug($payload['slug'] ?? null, $payload['name']);

        $role = $this->roles->create([
            'name' => $payload['name'],
            'slug' => $slug,
            'description' => $payload['description'] ?? null,
            'requires_mfa' => (bool) ($payload['requires_mfa'] ?? false),
        ], $this->roles->permissionIdsByUuids($payload['permission_ids'] ?? []));

        $this->audit->log('role.created', [
            'role_id' => $role->uuid,
            'name' => $role->name,
            'slug' => $role->slug,
            'permissions_count' => $role->permissions()->count(),
        ]);

        return $role;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(string $uuid, array $payload): Role
    {
        $role = $this->find($uuid);
        $isSystem = in_array($role->slug, self::SYSTEM_SLUGS, true);

        $data = [
            'name' => $payload['name'],
            'description' => $payload['description'] ?? null,
            'requires_mfa' => (bool) ($payload['requires_mfa'] ?? $role->requires_mfa),
        ];

        if (! $isSystem && array_key_exists('slug', $payload) && filled($payload['slug'])) {
            $data['slug'] = $this->resolveSlug((string) $payload['slug'], $payload['name'], $role->uuid);
        }

        $permissionIds = array_key_exists('permission_ids', $payload)
            ? $this->roles->permissionIdsByUuids($payload['permission_ids'] ?? [])
            : null;

        $updated = $this->roles->update($role, $data, $permissionIds);

        $this->audit->log('role.updated', [
            'role_id' => $updated->uuid,
            'name' => $updated->name,
            'slug' => $updated->slug,
            'permissions_count' => $updated->permissions()->count(),
        ]);

        return $updated;
    }

    public function delete(string $uuid): void
    {
        $role = $this->find($uuid);

        if (in_array($role->slug, self::SYSTEM_SLUGS, true)) {
            throw ValidationException::withMessages([
                'role' => ['System roles cannot be deleted.'],
            ]);
        }

        if (($role->users_count ?? $role->users()->count()) > 0) {
            throw ValidationException::withMessages([
                'role' => ['This role is assigned to users. Reassign them first.'],
            ]);
        }

        $meta = [
            'role_id' => $role->uuid,
            'name' => $role->name,
            'slug' => $role->slug,
        ];

        $this->roles->delete($role);

        $this->audit->log('role.deleted', $meta);
    }

    /**
     * @return Collection<int, \App\Models\Permission>
     */
    public function permissions(): Collection
    {
        return $this->roles->allPermissions();
    }

    public function isSystem(Role $role): bool
    {
        return in_array($role->slug, self::SYSTEM_SLUGS, true);
    }

    private function resolveSlug(?string $slug, string $name, ?string $ignoreUuid = null): string
    {
        $base = Str::slug($slug ?: $name);
        if ($base === '') {
            $base = 'role';
        }

        $candidate = $base;
        $i = 2;

        while (
            Role::query()
                ->where('slug', $candidate)
                ->when($ignoreUuid, fn ($q) => $q->where('uuid', '!=', $ignoreUuid))
                ->exists()
        ) {
            $candidate = "{$base}-{$i}";
            $i++;
        }

        return $candidate;
    }
}
