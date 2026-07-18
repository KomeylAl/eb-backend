<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $clientId = $this->route('client')?->id ?? $this->route('client');

        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => [
                'required',
                'string',
                'max:20',
                Rule::unique('users', 'phone')->ignore($clientId),
            ],
            'birth_date' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:500'],
            'password' => ['nullable', 'string', 'min:8'],
        ];
    }
}
