<?php

namespace App\Http\Requests\Comment;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'post_id' => ['required', 'uuid', 'exists:posts,id'],
            'body' => ['required', 'string'],
            'author_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'approved' => ['sometimes', 'boolean'],
        ];
    }
}
