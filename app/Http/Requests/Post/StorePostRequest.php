<?php

namespace App\Http\Requests\Post;

use App\Enums\PostStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:posts,slug'],
            'excerpt' => ['nullable', 'string'],
            'content' => ['required', 'string'],
            'thumbnail' => ['nullable', 'image', 'max:2048'],
            'status' => ['nullable', Rule::enum(PostStatus::class)],
            'published_at' => ['nullable', 'date'],
            'category_id' => ['nullable', 'uuid', 'exists:categories,id'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['uuid', 'exists:tags,id'],
        ];
    }
}
