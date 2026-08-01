<?php

namespace App\Http\Requests\Lookups;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLookupValueRequest extends FormRequest
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
            'type' => ['required', 'string', Rule::in(array_keys(config('lookups.types', [])))],
            'code' => ['required', 'string', 'max:60', 'regex:/^[a-z0-9_]+$/'],
            'label' => ['required', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Please choose a lookup type.',
            'type.in' => 'Please choose a valid lookup type.',
            'code.required' => 'Please enter a code.',
            'code.regex' => 'Code may only contain lowercase letters, numbers, and underscores.',
            'label.required' => 'Please enter a label.',
        ];
    }
}
