<?php

namespace App\Repositories\Security;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class RoleRepository
{
    /**
     * @param  array{search?: string}  $filters
     */
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Role::query()
            ->with('permissions')
            ->withCount('users')
            ->orderBy('name');

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $inner) use ($search): void {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    public function findByUuid(string $uuid): ?Role
    {
        return Role::query()
            ->with('permissions')
            ->withCount('users')
            ->where('uuid', $uuid)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<int>  $permissionIds
     */
    public function create(array $data, array $permissionIds = []): Role
    {
        /** @var Role $role */
        $role = Role::query()->create($data);

        if ($permissionIds !== []) {
            $role->permissions()->sync($permissionIds);
        }

        return $role->load('permissions')->loadCount('users');
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<int>|null  $permissionIds
     */
    public function update(Role $role, array $data, ?array $permissionIds = null): Role
    {
        $role->fill($data);
        $role->save();

        if ($permissionIds !== null) {
            $role->permissions()->sync($permissionIds);
        }

        return $role->load('permissions')->loadCount('users');
    }

    public function delete(Role $role): void
    {
        $role->permissions()->detach();
        $role->users()->detach();
        $role->delete();
    }

    /**
     * @return Collection<int, Permission>
     */
    public function allPermissions(): Collection
    {
        return Permission::query()
            ->orderBy('module')
            ->orderBy('name')
            ->get(['id', 'uuid', 'name', 'slug', 'module', 'description']);
    }

    /**
     * @param  list<string>  $uuids
     * @return list<int>
     */
    public function permissionIdsByUuids(array $uuids): array
    {
        if ($uuids === []) {
            return [];
        }

        return Permission::query()
            ->whereIn('uuid', $uuids)
            ->pluck('id')
            ->all();
    }
}
