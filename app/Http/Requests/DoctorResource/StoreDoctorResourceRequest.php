<?php

namespace App\Http\Requests\DoctorResource;

use App\Enums\ResourceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreDoctorResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::enum(ResourceType::class)],
            'description' => ['nullable', 'string'],
            'link' => ['nullable', 'required_if:type,link', 'prohibited_if:type,file', 'url', 'max:255'],
            'file' => ['nullable', 'required_if:type,file', 'prohibited_if:type,link', 'file', 'max:10240'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('type') === ResourceType::File->value && ! $this->hasFile('file')) {
                $validator->errors()->add('file', 'The file field is required when type is file.');
            }
        });
    }
}
