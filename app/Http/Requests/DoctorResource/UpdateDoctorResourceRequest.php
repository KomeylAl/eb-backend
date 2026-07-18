<?php

namespace App\Http\Requests\DoctorResource;

use App\Enums\ResourceType;
use App\Models\DoctorResource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateDoctorResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'string', Rule::enum(ResourceType::class)],
            'description' => ['nullable', 'string'],
            'link' => ['nullable', 'url', 'max:255'],
            'file' => ['nullable', 'file', 'max:10240'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var DoctorResource|null $resource */
            $resource = $this->route('doctorResource');
            $type = $this->input('type', $resource?->type?->value);

            if ($type === ResourceType::Link->value) {
                $hasLink = $this->filled('link') || filled($resource?->link);
                if (! $hasLink) {
                    $validator->errors()->add('link', 'The link field is required when type is link.');
                }
            }

            if ($type === ResourceType::File->value) {
                $hasFile = $this->hasFile('file') || filled($resource?->file_path);
                if (! $hasFile) {
                    $validator->errors()->add('file', 'The file field is required when type is file.');
                }
            }
        });
    }
}
