<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordWithOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'size:6'],
            'password' => ['required', 'string', 'confirmed', 'min:8'],
        ];
    }
}
