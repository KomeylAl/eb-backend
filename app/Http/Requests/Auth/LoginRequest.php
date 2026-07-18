<?php

namespace App\Http\Requests\Auth;

use App\Enums\UserType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string'],
            'type' => ['nullable', 'string', Rule::enum(UserType::class)],
        ];
    }
}
