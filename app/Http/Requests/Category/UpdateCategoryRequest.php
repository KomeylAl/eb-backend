<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('category')?->id ?? $this->route('category');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'slug')->ignore($categoryId),
            ],
            'excerpt' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
