<?php

namespace App\Http\Requests\Plus;

use Illuminate\Foundation\Http\FormRequest;

class PlusLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required_without:national_code', 'nullable', 'string', 'max:255'],
            'national_code' => ['required_without:password', 'nullable', 'string', 'max:20'],
        ];
    }

    public function secret(): string
    {
        return trim((string) ($this->input('password') ?: $this->input('national_code')));
    }
}
