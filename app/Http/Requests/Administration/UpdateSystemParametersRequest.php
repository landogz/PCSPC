<?php

namespace App\Http\Requests\Administration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSystemParametersRequest extends FormRequest
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
            'company_name' => ['required', 'string', 'max:200'],
            'company_short_name' => ['required', 'string', 'max:40'],
            'timezone' => ['required', 'string', Rule::in(config('system_parameters.timezones', ['Asia/Manila']))],
            'date_format' => ['required', 'string', Rule::in(config('system_parameters.date_formats', ['Y-m-d']))],
            'currency_code' => ['required', 'string', 'size:3'],
            'support_email' => ['required', 'email', 'max:150'],
            'leave_year_start_month' => ['required', 'integer', 'min:1', 'max:12'],
            'rest_day_holiday_paid_hours' => ['required', 'integer', 'min:0', 'max:24'],
            'default_grace_minutes' => ['required', 'integer', 'min:0', 'max:120'],
            'week_start' => ['required', 'string', Rule::in(config('system_parameters.week_starts', ['monday', 'sunday']))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'company_name.required' => 'Please enter the company name.',
            'company_short_name.required' => 'Please enter a short company name.',
            'timezone.required' => 'Please choose a timezone.',
            'timezone.in' => 'Please choose a valid timezone.',
            'date_format.required' => 'Please choose a date format.',
            'date_format.in' => 'Please choose a valid date format.',
            'currency_code.required' => 'Please enter a currency code.',
            'currency_code.size' => 'Currency code must be 3 letters (e.g. PHP).',
            'support_email.required' => 'Please enter a support email.',
            'support_email.email' => 'Please enter a valid support email.',
            'leave_year_start_month.required' => 'Please choose the leave year start month.',
            'rest_day_holiday_paid_hours.required' => 'Please enter rest-day holiday paid hours.',
            'default_grace_minutes.required' => 'Please enter default grace minutes.',
            'week_start.required' => 'Please choose the week start day.',
            'week_start.in' => 'Please choose a valid week start day.',
        ];
    }
}
