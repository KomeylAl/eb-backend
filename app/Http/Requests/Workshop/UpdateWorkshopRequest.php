<?php

namespace App\Http\Requests\Workshop;

use App\Enums\WorkshopType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkshopRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('type')) {
            $this->merge([
                'type' => WorkshopType::normalize((string) $this->input('type')),
            ]);
        }
    }

    public function rules(): array
    {
        $workshopId = $this->route('workshop')?->id ?? $this->route('workshop');

        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('workshops', 'slug')->ignore($workshopId),
            ],
            'type' => ['sometimes', 'required', 'string', Rule::enum(WorkshopType::class)],
            'excerpt' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'organizers' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'week_day' => ['nullable', 'string', 'max:50'],
            'time' => ['nullable', 'string', 'max:50'],
            'image' => ['nullable', 'image', 'max:5120'],
            'image_media_id' => ['nullable', 'uuid', 'exists:media,id'],
        ];
    }
}
