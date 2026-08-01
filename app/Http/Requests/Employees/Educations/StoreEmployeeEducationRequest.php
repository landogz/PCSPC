<?php

namespace App\Http\Requests\Employees\Educations;

use App\Models\EmployeeEducation;
use App\Support\LookupRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeEducationRequest extends FormRequest
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
        $currentYear = (int) now()->year;

        return [
            'institution' => ['required', 'string', 'max:150'],
            'level' => ['required', LookupRules::in('education_level', EmployeeEducation::LEVELS)],
            'degree_or_course' => ['nullable', 'string', 'max:150'],
            'year_started' => ['nullable', 'integer', 'min:1950', 'max:'.$currentYear],
            'year_ended' => ['nullable', 'integer', 'min:1950', 'max:'.($currentYear + 10), 'gte:year_started'],
            'is_highest' => ['sometimes', 'boolean'],
            'honors' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'institution.required' => 'Please enter the school or institution.',
            'level.required' => 'Please choose an education level.',
            'level.in' => 'Please choose a valid education level.',
            'year_ended.gte' => 'Year ended must be on or after year started.',
        ];
    }
}
