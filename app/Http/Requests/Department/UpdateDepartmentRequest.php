<?php

namespace App\Http\Requests\Department;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $departmentId = $this->route('department')?->id ?? $this->route('department');

        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('departments', 'slug')->ignore($departmentId),
            ],
            'excerpt' => ['nullable', 'string'],
            'content' => ['sometimes', 'string'],
            'thumbnail' => ['nullable', 'image', 'max:2048'],
            'thumbnail_media_id' => ['nullable', 'uuid', 'exists:media,id'],
        ];
    }
}
