<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ResendMfaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mfa_token' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'mfa_token.required' => 'MFA challenge token is required.',
        ];
    }
}
