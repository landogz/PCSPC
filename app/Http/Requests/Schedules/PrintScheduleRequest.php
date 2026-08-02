<?php

namespace App\Http\Requests\Schedules;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PrintScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('administration.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'scope' => ['required', 'string', Rule::in(['employee', 'department'])],
            'employee_id' => ['nullable', 'uuid'],
            'department_id' => ['nullable', 'uuid'],
            'effective' => ['nullable', 'string', Rule::in(['', 'current', 'upcoming', 'ended', 'all'])],
            'include_related' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'scope.required' => 'Please choose employee or department print layout.',
            'scope.in' => 'Please choose employee or department print layout.',
            'employee_id.uuid' => 'Please choose a valid employee.',
            'department_id.uuid' => 'Please choose a valid department.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('include_related')) {
            $this->merge([
                'include_related' => filter_var($this->input('include_related'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true,
            ]);
        }
    }
}
