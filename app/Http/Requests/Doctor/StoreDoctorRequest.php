<?php

namespace App\Http\Requests\Doctor;

use Illuminate\Foundation\Http\FormRequest;

class StoreDoctorRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:8'],
            'birth_date' => ['nullable', 'date'],
            'national_code' => ['required', 'string', 'size:10', 'unique:doctor_profiles,national_code'],
            'card_number' => ['nullable', 'string', 'max:16', 'unique:doctor_profiles,card_number'],
            'medical_number' => ['nullable', 'string', 'max:16', 'unique:doctor_profiles,medical_number'],
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
