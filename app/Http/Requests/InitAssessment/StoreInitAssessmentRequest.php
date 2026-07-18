<?php

namespace App\Http\Requests\InitAssessment;

use App\Enums\AssessmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInitAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['nullable', 'date'],
            'time' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::enum(AssessmentStatus::class)],
            'doctor_id' => ['nullable', 'uuid', 'exists:users,id'],
            'client' => ['required', 'array'],
            'client.name' => ['required', 'string', 'max:255'],
            'client.phone' => ['required', 'string', 'max:20'],
            'client.birth_date' => ['nullable', 'date'],
            'client.address' => ['nullable', 'string', 'max:500'],
        ];
    }
}
