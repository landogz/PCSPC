<?php

namespace App\Http\Resources\Security;

use App\Models\Role;
use App\Services\Security\RoleService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Role
 */
class RoleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $permissions = $this->relationLoaded('permissions')
            ? $this->permissions
            : collect();

        return [
            'id' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'requires_mfa' => (bool) $this->requires_mfa,
            'is_system' => in_array($this->slug, RoleService::SYSTEM_SLUGS, true),
            'users_count' => (int) ($this->users_count ?? 0),
            'permissions_count' => $permissions->count(),
            'permissions' => $permissions->map(fn ($permission) => [
                'id' => $permission->uuid,
                'name' => $permission->name,
                'slug' => $permission->slug,
                'module' => $permission->module,
            ])->values()->all(),
            'permission_ids' => $permissions->pluck('uuid')->values()->all(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
