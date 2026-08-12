<?php

namespace App\Http\Requests\TreatmentProgram;

use App\Enums\TreatmentProgramStatus;
use App\Enums\UserType;
use App\Support\DoctorUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTreatmentProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => [
                'required',
                'uuid',
                Rule::exists('users', 'id')->where('type', UserType::Client->value),
            ],
            'doctor_id' => [
                'required',
                'uuid',
                DoctorUser::existsRule(),
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::enum(TreatmentProgramStatus::class)],
            'started_at' => ['nullable', 'date'],
            'ended_at' => ['nullable', 'date', 'after_or_equal:started_at'],
        ];
    }
}
