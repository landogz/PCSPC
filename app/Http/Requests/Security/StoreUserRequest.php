<?php

namespace App\Http\Requests\Security;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
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
        return [
            'employee_id' => ['required', 'uuid', Rule::exists('employees', 'uuid')],
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
                Rule::unique('employees', 'email')->ignore($this->input('employee_id'), 'uuid'),
            ],
            'password' => ['required', 'string', Password::defaults()],
            'is_active' => ['sometimes', 'boolean'],
            'mfa_enabled' => ['sometimes', 'boolean'],
            'role_ids' => ['required', 'array', 'size:1'],
            'role_ids.*' => ['uuid', Rule::exists('roles', 'uuid')],
        ];
    }
}
