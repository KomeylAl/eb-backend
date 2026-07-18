<?php

namespace App\Http\Requests\Doctor;

use Illuminate\Foundation\Http\FormRequest;

class SetDoctorPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'min:8'],
        ];
    }
}
