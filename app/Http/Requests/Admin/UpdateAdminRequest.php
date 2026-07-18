<?php

namespace App\Http\Requests\Admin;

use App\Enums\AdminRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $adminId = $this->route('admin')?->id ?? $this->route('admin');

        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => [
                'required',
                'string',
                'max:20',
                Rule::unique('users', 'phone')->ignore($adminId),
            ],
            'password' => ['nullable', 'string', 'min:8'],
            'birth_date' => ['nullable', 'date'],
            'admin_role' => ['required', 'string', Rule::enum(AdminRole::class)],
        ];
    }
}
