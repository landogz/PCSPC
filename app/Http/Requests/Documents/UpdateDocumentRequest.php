<?php

namespace App\Http\Requests\Documents;

use App\Models\EmployeeDocument;
use App\Support\LookupRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDocumentRequest extends FormRequest
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
            'employee_id' => ['sometimes', 'uuid', 'exists:employees,uuid'],
            'title' => ['required', 'string', 'max:180'],
            'category' => ['required', LookupRules::in('document_category', EmployeeDocument::CATEGORIES)],
            'file' => [
                'nullable',
                'file',
                'max:10240',
                'mimes:pdf,jpg,jpeg,png,webp,doc,docx',
            ],
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:issued_at'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Please enter a document title.',
            'category.required' => 'Please choose a category.',
            'category.in' => 'Please choose a valid category.',
            'file.mimes' => 'Allowed types: PDF, JPG, PNG, WebP, DOC, DOCX.',
            'file.max' => 'Document must be 10 MB or smaller.',
            'expires_at.after_or_equal' => 'Expiry date must be on or after the issued date.',
        ];
    }
}
