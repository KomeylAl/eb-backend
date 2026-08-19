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
        $merge = [];

        if ($this->filled('type')) {
            $merge['type'] = WorkshopType::normalize((string) $this->input('type'));
        }

        if ($this->has('registration_open')) {
            $merge['registration_open'] = filter_var(
                $this->input('registration_open'),
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            ) ?? true;
        }

        if ($merge !== []) {
            $this->merge($merge);
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
            'registration_open' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'image', 'max:5120'],
            'image_media_id' => ['nullable', 'uuid', 'exists:media,id'],
        ];
    }
}
