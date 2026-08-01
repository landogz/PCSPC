<?php

namespace App\Http\Requests\Shifts;

use Illuminate\Foundation\Http\FormRequest;

class StoreShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('administration.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        foreach (['time_in', 'time_out'] as $field) {
            $value = $this->input($field);
            if (is_string($value) && preg_match('/^\d{1,2}:\d{2}/', $value) === 1) {
                $this->merge([$field => substr($value, 0, 5)]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:30', 'unique:shifts,code'],
            'name' => ['required', 'string', 'max:150'],
            'time_in' => ['required', 'date_format:H:i'],
            'time_out' => ['required', 'date_format:H:i'],
            'break_minutes' => ['sometimes', 'integer', 'min:0', 'max:480'],
            'grace_minutes' => ['sometimes', 'integer', 'min:0', 'max:120'],
            'crosses_midnight' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Please enter a shift code.',
            'code.unique' => 'This shift code is already in use.',
            'name.required' => 'Please enter a shift name.',
            'time_in.required' => 'Please enter the time in.',
            'time_in.date_format' => 'Time in must be in HH:MM format.',
            'time_out.required' => 'Please enter the time out.',
            'time_out.date_format' => 'Time out must be in HH:MM format.',
        ];
    }
}
