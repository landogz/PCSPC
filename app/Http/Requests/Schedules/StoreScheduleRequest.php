<?php

namespace App\Http\Requests\Schedules;

use App\Models\ShiftSchedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('administration.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('effective_to') === '') {
            $this->merge(['effective_to' => null]);
        }
        if ($this->input('notes') === '') {
            $this->merge(['notes' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'shift_id' => ['required', 'uuid', Rule::exists('shifts', 'uuid')],
            'assignee_type' => ['required', Rule::in(ShiftSchedule::ASSIGNEE_TYPES)],
            'employee_id' => [
                'nullable',
                'uuid',
                Rule::exists('employees', 'uuid'),
                Rule::requiredIf(fn () => $this->input('assignee_type') === ShiftSchedule::ASSIGNEE_EMPLOYEE),
            ],
            'department_id' => [
                'nullable',
                'uuid',
                Rule::exists('departments', 'uuid'),
                Rule::requiredIf(fn () => $this->input('assignee_type') === ShiftSchedule::ASSIGNEE_DEPARTMENT),
            ],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'days_of_week' => ['nullable', 'array'],
            'days_of_week.*' => ['integer', 'min:1', 'max:7'],
            'notes' => ['nullable', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'shift_id.required' => 'Please choose a shift template.',
            'assignee_type.required' => 'Please choose employee or department.',
            'employee_id.required' => 'Please select an employee.',
            'department_id.required' => 'Please select a department.',
            'effective_from.required' => 'Please choose an effective start date.',
            'effective_to.after_or_equal' => 'End date must be on or after the start date.',
        ];
    }
}
