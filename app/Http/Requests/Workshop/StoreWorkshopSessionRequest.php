<?php

namespace App\Http\Requests\Workshop;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkshopSessionRequest extends FormRequest
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
            'session_date' => ['nullable', 'date'],
            'start_time' => ['nullable', 'string', 'max:50'],
            'end_time' => ['nullable', 'string', 'max:50'],
            'location' => ['nullable', 'string', 'max:255'],
            'link' => ['nullable', 'string', 'max:500'],
        ];
    }
}
