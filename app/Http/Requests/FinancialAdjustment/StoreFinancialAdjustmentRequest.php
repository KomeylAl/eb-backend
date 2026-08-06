<?php

namespace App\Http\Requests\FinancialAdjustment;

use App\Enums\FinancialAdjustmentStatus;
use App\Enums\FinancialAdjustmentType;
use App\Enums\UserType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFinancialAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => [
                'required',
                'uuid',
                Rule::exists('users', 'id')->where('type', UserType::Client->value),
            ],
            'appointment_id' => ['nullable', 'uuid', 'exists:appointments,id'],
            'invoice_id' => ['nullable', 'uuid', 'exists:invoices,id'],
            'type' => ['required', 'string', Rule::enum(FinancialAdjustmentType::class)],
            'amount' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', 'string', Rule::enum(FinancialAdjustmentStatus::class)],
        ];
    }
}
