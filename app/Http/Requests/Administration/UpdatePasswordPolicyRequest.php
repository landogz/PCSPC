<?php

namespace App\Http\Requests\Administration;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePasswordPolicyRequest extends FormRequest
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
            'min_length' => ['required', 'integer', 'min:6', 'max:64'],
            'require_mixed_case' => ['required', 'boolean'],
            'require_numbers' => ['required', 'boolean'],
            'require_symbols' => ['required', 'boolean'],
            'uncompromised' => ['required', 'boolean'],
            'expire_days' => ['required', 'integer', 'min:0', 'max:730'],
            'history_count' => ['required', 'integer', 'min:0', 'max:24'],
            'force_change_temporary' => ['required', 'boolean'],
        ];
    }
}
