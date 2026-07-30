<?php

namespace App\Http\Requests\Security;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('users.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $uuid = (string) $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($uuid, 'uuid'),
            ],
            'password' => ['nullable', 'string', Password::defaults()],
            'is_active' => ['sometimes', 'boolean'],
            'mfa_enabled' => ['sometimes', 'boolean'],
            'role_ids' => ['required', 'array', 'size:1'],
            'role_ids.*' => ['uuid', Rule::exists('roles', 'uuid')],
        ];
    }
}
