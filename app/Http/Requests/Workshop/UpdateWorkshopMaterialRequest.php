<?php

namespace App\Http\Requests\Workshop;

use App\Enums\ResourceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateWorkshopMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['sometimes', 'required', 'string', Rule::enum(ResourceType::class)],
            'description' => ['nullable', 'string'],
            'link' => ['nullable', 'url', 'max:500'],
            'file' => [
                'nullable',
                'file',
                'max:20480',
                'mimes:pdf,jpg,jpeg,png,webp,zip,ppt,pptx,doc,docx',
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $type = $this->input('type');
            if ($type === ResourceType::Link->value && $this->hasFile('file')) {
                $validator->errors()->add('file', 'File is not allowed when type is link.');
            }
            if ($type === ResourceType::File->value && $this->filled('link')) {
                $validator->errors()->add('link', 'Link is not allowed when type is file.');
            }
        });
    }
}
