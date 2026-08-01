<?php

namespace App\Http\Requests\Administration;

use Illuminate\Foundation\Http\FormRequest;

class UploadSystemLogoRequest extends FormRequest
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
            'logo' => [
                'required',
                'file',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:2048',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'logo.required' => 'Please choose a logo image.',
            'logo.image' => 'Logo must be an image file.',
            'logo.mimes' => 'Logo must be JPG, PNG, or WebP.',
            'logo.max' => 'Logo must be 2 MB or smaller.',
        ];
    }
}
