<?php

namespace App\Http\Requests\Homework;

use App\Enums\HomeworkStatus;
use App\Enums\HomeworkType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHomeworkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::enum(HomeworkType::class)],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'meta' => ['nullable', 'array'],
            'status' => ['nullable', 'string', Rule::enum(HomeworkStatus::class)],
            'due_at' => ['nullable', 'date'],
        ];
    }
}
