<?php

namespace App\Http\Requests\Overtime;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOvertimeRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $min = (float) config('workflow.ot_min_hours', 0.25);
        $max = (float) config('workflow.ot_max_hours', 24);

        return [
            'kind' => ['required', 'string', Rule::in(['ot', 'ot_meal'])],
            'work_date' => ['required', 'date'],
            'hours' => ['required', 'numeric', "min:{$min}", "max:{$max}"],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
            'meal_notes' => ['nullable', 'string', 'max:500', 'required_if:kind,ot_meal'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kind.required' => 'Please choose OT or OT Meal.',
            'work_date.required' => 'Please choose the work date.',
            'hours.required' => 'Please enter the number of hours.',
            'reason.required' => 'Please provide a reason.',
            'reason.min' => 'Reason must be at least 3 characters.',
            'meal_notes.required_if' => 'Meal notes are required for OT Meal filings.',
        ];
    }
}
