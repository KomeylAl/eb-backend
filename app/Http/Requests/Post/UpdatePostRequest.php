<?php

namespace App\Http\Requests\Post;

use App\Enums\PostStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $postId = $this->route('post')?->id ?? $this->route('post');

        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('posts', 'slug')->ignore($postId),
            ],
            'excerpt' => ['nullable', 'string'],
            'content' => ['sometimes', 'required', 'string'],
            'thumbnail' => ['nullable', 'image', 'max:2048'],
            'status' => ['sometimes', Rule::enum(PostStatus::class)],
            'published_at' => ['nullable', 'date'],
            'category_id' => ['nullable', 'uuid', 'exists:categories,id'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['uuid', 'exists:tags,id'],
        ];
    }
}
