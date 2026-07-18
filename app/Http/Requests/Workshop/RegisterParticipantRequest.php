<?php

namespace App\Http\Requests\Workshop;

use App\Enums\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterParticipantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'english_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'national_code' => ['nullable', 'string', 'max:20'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'approved' => ['sometimes', 'boolean'],
        ];
    }
}
