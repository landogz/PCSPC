<?php

namespace App\Http\Resources;

use App\Models\User;
use App\Services\Administration\PasswordPolicyService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = app(PasswordPolicyService::class)->statusFor($this->resource);

        return array_merge([
            'id' => $this->uuid,
            'name' => $this->name,
            'email' => $this->email,
            'employee_number' => $this->employee_number,
            'is_active' => $this->is_active,
            'mfa_enabled' => $this->mfa_enabled,
            'roles' => $this->roleSlugs(),
            'permissions' => $this->permissionSlugs(),
            'last_login_at' => $this->last_login_at?->toIso8601String(),
        ], $status);
    }
}
