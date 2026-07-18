<?php

namespace App\Http\Requests\About;

use Illuminate\Foundation\Http\FormRequest;

class UpsertAboutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'about' => ['required', 'string'],
            'address' => ['nullable', 'string', 'max:500'],
            'phones' => ['nullable', 'string', 'max:255'],
            'mobile_phones' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'string', 'max:50'],
            'longitude' => ['nullable', 'string', 'max:50'],
            'logo' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
