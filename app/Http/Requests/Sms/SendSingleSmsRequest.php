<?php

namespace App\Http\Requests\Sms;

use Illuminate\Foundation\Http\FormRequest;

class SendSingleSmsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:20'],
            'message' => ['required', 'string'],
        ];
    }
}
