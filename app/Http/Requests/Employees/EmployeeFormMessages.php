<?php

namespace App\Http\Requests\Employees;

trait EmployeeFormMessages
{
    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'Please enter a first name.',
            'last_name.required' => 'Please enter a last name.',
            'employee_number.required' => 'Please enter an employee number.',
            'employee_number.unique' => 'That employee number is already in use.',
            'email.required' => 'Please enter a login email.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'That email is already linked to another employee.',
            'employment_status.required' => 'Please choose an employment status.',
            'employment_status.in' => 'Please choose a valid employment status.',
            'department_id.exists' => 'Please select a valid department.',
            'date_regularized.after_or_equal' => 'Date regularized must be on or after the hire date.',
            'birth_date.before' => 'Birth date must be before today.',
            'photo.image' => 'Please upload a valid image file.',
            'photo.mimes' => 'Photo must be a JPG, PNG, or WebP file.',
            'photo.max' => 'Photo must be 2 MB or smaller.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'first_name' => 'first name',
            'last_name' => 'last name',
            'employee_number' => 'employee number',
            'employment_status' => 'employment status',
            'department_id' => 'department',
            'position_title' => 'position',
            'date_hired' => 'date hired',
            'date_regularized' => 'date regularized',
            'date_separated' => 'date separated',
            'birth_date' => 'birth date',
            'civil_status' => 'civil status',
            'address_line' => 'address',
            'zip_code' => 'ZIP code',
            'sss_number' => 'SSS number',
            'philhealth_number' => 'PhilHealth number',
            'pagibig_number' => 'Pag-IBIG number',
        ];
    }
}
