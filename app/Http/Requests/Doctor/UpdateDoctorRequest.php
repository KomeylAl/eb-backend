<?php

namespace App\Http\Requests\Doctor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDoctorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['days', 'times', 'department_ids'] as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $decoded = json_decode($this->input($field), true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $this->merge([$field => $decoded]);
                }
            }
        }
    }

    public function rules(): array
    {
        $doctor = $this->route('doctor');
        $doctorId = is_object($doctor) ? $doctor->id : $doctor;
        $profileId = is_object($doctor) ? $doctor->doctorProfile?->id : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => [
                'required',
                'string',
                'max:20',
                Rule::unique('users', 'phone')->ignore($doctorId),
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($doctorId),
            ],
            'password' => ['nullable', 'string', 'min:8'],
            'birth_date' => ['nullable', 'date'],
            'national_code' => [
                'required',
                'string',
                'size:10',
                Rule::unique('doctor_profiles', 'national_code')->ignore($profileId),
            ],
            'card_number' => [
                'nullable',
                'string',
                'max:16',
                Rule::unique('doctor_profiles', 'card_number')->ignore($profileId),
            ],
            'medical_number' => [
                'nullable',
                'string',
                'max:16',
                Rule::unique('doctor_profiles', 'medical_number')->ignore($profileId),
            ],
            'avatar' => ['nullable', 'image', 'max:10240'],
            'avatar_media_id' => ['nullable', 'uuid', 'exists:media,id'],
            'days' => ['nullable', 'array'],
            'times' => ['nullable', 'array'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'department_ids' => ['nullable', 'array'],
            'department_ids.*' => ['uuid', 'exists:departments,id'],
        ];
    }
}
