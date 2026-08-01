<?php

namespace App\Http\Requests\Employees\EmploymentHistory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreEmployeeEmploymentHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('employees.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'employer_name' => ['required', 'string', 'max:150'],
            'position_title' => ['required', 'string', 'max:150'],
            'location' => ['nullable', 'string', 'max:150'],
            'date_from' => ['required', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'is_current' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'employer_name.required' => 'Please enter the employer name.',
            'position_title.required' => 'Please enter the position title.',
            'date_from.required' => 'Please enter the start date.',
            'date_to.after_or_equal' => 'End date must be on or after the start date.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $isCurrent = $this->boolean('is_current');
            $dateTo = $this->input('date_to');

            if (! $isCurrent && ($dateTo === null || $dateTo === '')) {
                $validator->errors()->add('date_to', 'Please enter an end date, or mark this as the current job.');
            }
        });
    }
}
