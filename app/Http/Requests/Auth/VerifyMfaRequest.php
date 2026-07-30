<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyMfaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $length = (int) config('auth_security.mfa_otp_length', 6);

        return [
            'mfa_token' => ['required', 'string'],
            'otp' => ['required', 'string', 'size:'.$length],
        ];
    }
}
