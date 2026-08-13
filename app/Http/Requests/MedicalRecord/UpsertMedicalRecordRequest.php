<?php

namespace App\Http\Requests\MedicalRecord;

use App\Models\MedicalRecord;
use App\Models\TreatmentProgram;
use App\Support\DoctorUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertMedicalRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var TreatmentProgram|string|null $program */
        $program = $this->route('treatment_program');
        $programId = $program instanceof TreatmentProgram ? $program->id : $program;

        $existingId = MedicalRecord::query()
            ->where('treatment_program_id', $programId)
            ->value('id');

        return [
            'record_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('medical_records', 'record_number')->ignore($existingId),
            ],
            'reference_source' => ['nullable', 'string', 'max:255'],
            'admission_date' => ['nullable', 'date'],
            'visit_date' => ['nullable', 'date'],
            'doctor_id' => ['nullable', 'uuid', DoctorUser::existsRule()],
            'supervisor_id' => ['nullable', 'uuid', DoctorUser::existsRule()],
            'admin_id' => ['nullable', 'uuid', 'exists:users,id'],
            'chief_complaints' => ['nullable', 'string'],
            'present_illness' => ['nullable', 'string'],
            'past_history' => ['nullable', 'string'],
            'family_history' => ['nullable', 'string'],
            'personal_history' => ['nullable', 'string'],
            'mse' => ['nullable', 'string'],
            'diagnosis' => ['nullable', 'string'],
            'companion_name' => ['nullable', 'string', 'max:255'],
            'companion_phone' => ['nullable', 'string', 'max:20'],
            'companion_address' => ['nullable', 'string', 'max:500'],
            'companion_birth_date' => ['nullable', 'date'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
