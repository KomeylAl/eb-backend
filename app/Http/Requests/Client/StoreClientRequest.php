<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'birth_date' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:500'],
            'password' => ['nullable', 'string', 'min:8'],
        ];
    }
}
