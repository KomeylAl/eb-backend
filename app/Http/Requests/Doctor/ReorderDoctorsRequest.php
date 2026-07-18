<?php

namespace App\Http\Requests\Doctor;

use App\Support\DoctorUser;
use Illuminate\Foundation\Http\FormRequest;

class ReorderDoctorsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ordered_ids' => ['required', 'array', 'min:1'],
            'ordered_ids.*' => [
                'required',
                'uuid',
                'distinct',
                DoctorUser::existsRule(),
            ],
        ];
    }
}
