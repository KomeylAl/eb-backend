<?php

namespace App\Http\Requests\Sms;

use Illuminate\Foundation\Http\FormRequest;

class SendMultiSmsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phones' => ['required', 'array', 'min:1'],
            'phones.*' => ['required', 'string', 'max:20'],
            'message' => ['required', 'string'],
        ];
    }
}
