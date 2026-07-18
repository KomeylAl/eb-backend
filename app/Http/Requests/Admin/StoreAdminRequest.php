<?php

namespace App\Http\Requests\Admin;

use App\Enums\AdminRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdminRequest extends FormRequest
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
            'password' => ['required', 'string', 'min:8'],
            'birth_date' => ['nullable', 'date'],
            'admin_role' => ['required', 'string', Rule::enum(AdminRole::class)],
        ];
    }
}
