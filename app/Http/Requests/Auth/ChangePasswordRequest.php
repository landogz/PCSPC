<?php

namespace App\Http\Requests\Auth;

use App\Services\Administration\PasswordPolicyService;
use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var PasswordPolicyService $policy */
        $policy = app(PasswordPolicyService::class);

        return [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', $policy->validationRule()],
        ];
    }
}
