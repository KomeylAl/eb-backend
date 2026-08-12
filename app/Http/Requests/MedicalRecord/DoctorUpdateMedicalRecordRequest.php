<?php

namespace App\Http\Requests\MedicalRecord;

use Illuminate\Foundation\Http\FormRequest;

class DoctorUpdateMedicalRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'visit_date' => ['nullable', 'date'],
            'chief_complaints' => ['nullable', 'string'],
            'present_illness' => ['nullable', 'string'],
            'past_history' => ['nullable', 'string'],
            'family_history' => ['nullable', 'string'],
            'personal_history' => ['nullable', 'string'],
            'mse' => ['nullable', 'string'],
            'diagnosis' => ['nullable', 'string'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
