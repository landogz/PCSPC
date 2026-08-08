<?php

namespace App\Http\Requests\Leave;

use Illuminate\Foundation\Http\FormRequest;

class AdjustLeaveBalanceRequest extends FormRequest
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
        return [
            'employee_id' => ['required', 'uuid', 'exists:employees,uuid'],
            'leave_type_id' => ['required', 'uuid', 'exists:leave_types,uuid'],
            'leave_year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'amount' => ['required', 'numeric', 'not_in:0', 'between:-365,365'],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
            'effective_date' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'employee_id.required' => 'Please choose an employee.',
            'leave_type_id.required' => 'Please choose a leave type.',
            'amount.required' => 'Please enter an adjustment amount.',
            'amount.not_in' => 'Adjustment amount cannot be zero.',
            'reason.required' => 'Please provide a reason for the adjustment.',
            'reason.min' => 'Reason must be at least 3 characters.',
        ];
    }
}
