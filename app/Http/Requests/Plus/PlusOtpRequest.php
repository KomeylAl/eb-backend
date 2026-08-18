<?php

namespace App\Http\Requests\Plus;

use Illuminate\Foundation\Http\FormRequest;

class PlusOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:20'],
        ];
    }
}
