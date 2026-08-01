<?php

namespace App\Http\Requests\Holidays;

use App\Models\Holiday;
use App\Support\LookupRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHolidayRequest extends FormRequest
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
        $holidayUuid = (string) $this->route('holiday');
        $holidayId = Holiday::query()->where('uuid', $holidayUuid)->value('id');

        return [
            'name' => ['required', 'string', 'max:150'],
            'holiday_date' => [
                'required',
                'date',
                Rule::unique('holidays', 'holiday_date')
                    ->where(fn ($query) => $query->where('name', $this->input('name')))
                    ->ignore($holidayId),
            ],
            'type' => ['required', LookupRules::in('holiday_type', Holiday::TYPES)],
            'is_recurring' => ['sometimes', 'boolean'],
            'is_double_pay' => ['sometimes', 'boolean'],
            'paid_hours' => ['sometimes', 'integer', 'min:0', 'max:24'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please enter a holiday name.',
            'holiday_date.required' => 'Please choose a holiday date.',
            'holiday_date.unique' => 'This holiday name and date already exist.',
            'type.required' => 'Please choose a holiday type.',
            'type.in' => 'Please choose a valid holiday type.',
        ];
    }
}
