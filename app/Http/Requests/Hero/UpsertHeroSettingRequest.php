<?php

namespace App\Http\Requests\Hero;

use Illuminate\Foundation\Http\FormRequest;

class UpsertHeroSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'autoplay_ms' => ['nullable', 'integer', 'min:2000', 'max:60000'],
            'background' => ['nullable', 'image', 'max:10240'],
            'background_media_id' => ['nullable', 'uuid', 'exists:media,id'],
        ];
    }
}
