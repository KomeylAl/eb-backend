<?php

namespace App\Http\Requests\Comment;

use App\Enums\CommentableType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'commentable_type' => ['required', 'string', Rule::enum(CommentableType::class)],
            'commentable_id' => ['required', 'uuid'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'body' => ['required', 'string'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
        ];
    }
}
