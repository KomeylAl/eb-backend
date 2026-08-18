<?php

namespace App\Http\Requests\Workshop;

use App\Enums\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkshopParticipantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'english_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['sometimes', 'required', 'string', 'max:20'],
            'national_code' => ['nullable', 'string', 'max:20'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'approved' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Admin forms historically sent `name_en`
        if ($this->has('name_en') && ! $this->has('english_name')) {
            $this->merge(['english_name' => $this->input('name_en')]);
        }
    }
}
