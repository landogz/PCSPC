<?php

namespace App\Http\Requests\Employees\CareerHistory;

use App\Models\EmployeeCareerHistory;
use App\Support\LookupRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreEmployeeCareerHistoryRequest extends FormRequest
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
            'position_title' => ['required', 'string', 'max:150'],
            'employment_category' => [
                'required',
                'string',
                'max:50',
                LookupRules::in('employment_category', EmployeeCareerHistory::CATEGORIES),
            ],
            'basic_salary' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'salary_rate_type' => ['required', 'string', Rule::in(EmployeeCareerHistory::RATE_TYPES)],
            'currency' => ['nullable', 'string', 'size:3'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
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
            'position_title.required' => 'Please enter the position title.',
            'employment_category.required' => 'Please choose an employment category.',
            'employment_category.in' => 'Please choose a valid employment category.',
            'salary_rate_type.required' => 'Please choose a salary rate type.',
            'salary_rate_type.in' => 'Please choose a valid salary rate type.',
            'effective_from.required' => 'Please enter the effective date.',
            'effective_to.after_or_equal' => 'End date must be on or after the effective date.',
            'basic_salary.numeric' => 'Salary must be a valid number.',
            'basic_salary.min' => 'Salary cannot be negative.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $isCurrent = $this->boolean('is_current');
            $effectiveTo = $this->input('effective_to');

            if (! $isCurrent && ($effectiveTo === null || $effectiveTo === '')) {
                $validator->errors()->add(
                    'effective_to',
                    'Please enter an end date, or mark this as the current record.'
                );
            }
        });
    }
}
