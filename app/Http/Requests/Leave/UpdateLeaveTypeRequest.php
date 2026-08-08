<?php

namespace App\Http\Requests\Leave;

use App\Models\LeaveType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeaveTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('leave.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper(trim((string) $this->input('code', ''))),
            'is_accruing' => $this->boolean('is_accruing'),
            'requires_reason' => $this->boolean('requires_reason', true),
            'requires_hr' => $this->boolean('requires_hr'),
            'is_active' => $this->boolean('is_active', true),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $typeId = LeaveType::query()
            ->where('uuid', (string) $this->route('leaveType'))
            ->value('id');

        return [
            'code' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Z0-9_\\-]+$/',
                Rule::unique('leave_types', 'code')->ignore($typeId),
            ],
            'name' => ['required', 'string', 'max:100'],
            'is_accruing' => ['sometimes', 'boolean'],
            'requires_reason' => ['sometimes', 'boolean'],
            'requires_hr' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Please enter a leave type code.',
            'code.unique' => 'This leave type code is already in use.',
            'code.regex' => 'Code may only use letters, numbers, underscore, or hyphen.',
            'name.required' => 'Please enter a leave type name.',
        ];
    }
}
