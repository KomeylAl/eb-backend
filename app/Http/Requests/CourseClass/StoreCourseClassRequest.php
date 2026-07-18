<?php

namespace App\Http\Requests\CourseClass;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourseClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'week_day' => ['nullable', 'string', 'max:50'],
            'time' => ['nullable', 'string', 'max:50'],
            'teacher_id' => ['required', 'uuid', 'exists:users,id'],
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['uuid', 'exists:users,id'],
            'dates' => ['nullable', 'array'],
            'dates.*' => ['date'],
        ];
    }
}
