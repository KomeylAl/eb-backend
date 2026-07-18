<?php

namespace App\Http\Requests\Tag;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tagId = $this->route('tag')?->id ?? $this->route('tag');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('tags', 'slug')->ignore($tagId),
            ],
            'excerpt' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
