<?php

namespace App\Http\Requests\Employees\Dependents;

use App\Models\EmployeeDependent;
use App\Support\LookupRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeDependentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('employees.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('gender') === '') {
            $this->merge(['gender' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'suffix' => ['nullable', 'string', 'max:20'],
            'relationship' => ['required', LookupRules::in('dependent_relationship', EmployeeDependent::RELATIONSHIPS)],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'string', 'max:60', LookupRules::in('gender')],
            'mobile' => ['nullable', 'string', 'max:30'],
            'is_beneficiary' => ['sometimes', 'boolean'],
            'is_emergency_contact' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'Please enter a first name.',
            'last_name.required' => 'Please enter a last name.',
            'relationship.required' => 'Please choose a relationship.',
            'relationship.in' => 'Please choose a valid relationship.',
            'birth_date.before' => 'Birth date must be before today.',
        ];
    }
}
