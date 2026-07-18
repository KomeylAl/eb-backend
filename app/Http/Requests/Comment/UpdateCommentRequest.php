<?php

namespace App\Http\Requests\Comment;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['sometimes', 'required', 'string'],
            'author_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'approved' => ['sometimes', 'boolean'],
        ];
    }
}
