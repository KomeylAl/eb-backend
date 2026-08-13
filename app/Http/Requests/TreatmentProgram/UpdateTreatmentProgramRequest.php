<?php

namespace App\Http\Requests\TreatmentProgram;

use App\Enums\TreatmentProgramStatus;
use App\Support\DoctorUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTreatmentProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'string', Rule::enum(TreatmentProgramStatus::class)],
            'doctor_id' => ['sometimes', 'uuid', DoctorUser::existsRule()],
            'started_at' => ['sometimes', 'nullable', 'date'],
            'ended_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
