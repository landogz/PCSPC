<?php

namespace App\Http\Resources\Security;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class SecurityUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $roles = $this->relationLoaded('roles')
            ? $this->roles
            : $this->roles()->get(['roles.id', 'roles.uuid', 'roles.name', 'roles.slug']);

        return [
            'id' => $this->uuid,
            'name' => $this->name,
            'email' => $this->email,
            'employee_number' => $this->employee_number,
            'is_active' => (bool) $this->is_active,
            'is_protected' => $this->isProtected(),
            'can_delete' => ! $this->isProtected(),
            'mfa_enabled' => (bool) $this->mfa_enabled,
            'is_locked' => $this->isLocked(),
            'failed_login_attempts' => (int) $this->failed_login_attempts,
            'locked_until' => $this->locked_until?->toIso8601String(),
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'last_login_ip' => $this->last_login_ip,
            'password_changed_at' => $this->password_changed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'roles' => $roles->map(fn ($role) => [
                'id' => $role->uuid,
                'name' => $role->name,
                'slug' => $role->slug,
            ])->values()->all(),
            'role_slugs' => $roles->pluck('slug')->values()->all(),
        ];
    }
}
