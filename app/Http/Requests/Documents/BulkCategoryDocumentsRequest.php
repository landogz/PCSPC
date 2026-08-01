<?php

namespace App\Http\Requests\Documents;

use App\Models\EmployeeDocument;
use App\Support\LookupRules;
use Illuminate\Foundation\Http\FormRequest;

class BulkCategoryDocumentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('documents.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['required', 'uuid'],
            'category' => ['required', LookupRules::in('document_category', EmployeeDocument::CATEGORIES)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ids.required' => 'Select at least one document.',
            'category.required' => 'Please choose a category.',
            'category.in' => 'Please choose a valid category.',
        ];
    }
}
