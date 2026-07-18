<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Foundation\Http\FormRequest;

class GenerateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'uuid', 'exists:users,id'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'admin_id' => ['nullable', 'uuid', 'exists:users,id'],
        ];
    }
}
