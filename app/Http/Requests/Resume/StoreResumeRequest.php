<?php

namespace App\Http\Requests\Resume;

use Illuminate\Foundation\Http\FormRequest;

class StoreResumeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['educations', 'experiences', 'skills', 'certifications', 'social_links'] as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $decoded = json_decode($this->input($field), true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $this->merge([$field => $decoded]);
                }
            }
        }
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'educations' => ['nullable', 'array'],
            'experiences' => ['nullable', 'array'],
            'skills' => ['nullable', 'array'],
            'certifications' => ['nullable', 'array'],
            'social_links' => ['nullable', 'array'],
            'content' => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'mimes:pdf', 'max:4096'],
        ];
    }
}
