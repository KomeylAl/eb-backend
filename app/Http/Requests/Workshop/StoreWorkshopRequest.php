<?php

namespace App\Http\Requests\Workshop;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkshopRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:workshops,slug'],
            'excerpt' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'organizers' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'week_day' => ['nullable', 'string', 'max:50'],
            'time' => ['nullable', 'string', 'max:50'],
            'image' => ['nullable', 'image', 'max:5120'],
            'image_media_id' => ['nullable', 'uuid', 'exists:media,id'],
            'image_media_id' => ['nullable', 'uuid', 'exists:media,id'],
        ];
    }
}
