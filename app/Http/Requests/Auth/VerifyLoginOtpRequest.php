<?php

namespace App\Http\Requests\Auth;

use App\Enums\UserType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifyLoginOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:20'],
            'type' => ['required', 'string', Rule::in([UserType::Admin->value, UserType::Doctor->value])],
            'code' => ['required', 'string', 'size:6'],
        ];
    }
}
