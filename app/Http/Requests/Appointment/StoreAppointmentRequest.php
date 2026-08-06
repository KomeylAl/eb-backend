<?php

namespace App\Http\Requests\Appointment;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserType;
use App\Support\DoctorUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'doctor_id' => [
                'required',
                'uuid',
                DoctorUser::existsRule(),
            ],
            'client_id' => [
                'required',
                'uuid',
                Rule::exists('users', 'id')->where('type', UserType::Client->value),
            ],
            'date' => ['required', 'date'],
            'time' => ['required', 'string', 'max:20'],
            'amount' => ['required', 'integer', 'min:0'],
            'service' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', Rule::enum(AppointmentStatus::class)],
            'payment_status' => ['required', 'string', Rule::enum(PaymentStatus::class)],
            'paid_amount' => ['nullable', 'integer', 'min:0'],
            'payment_method' => ['nullable', 'string', Rule::enum(PaymentMethod::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('payment_status') !== PaymentStatus::Partial->value) {
                return;
            }

            $amount = (int) $this->input('amount');
            $paidAmount = $this->input('paid_amount');

            if ($paidAmount === null) {
                $validator->errors()->add('paid_amount', 'The paid_amount field is required when payment_status is partial.');

                return;
            }

            $paidAmount = (int) $paidAmount;

            if ($paidAmount <= 0 || $paidAmount >= $amount) {
                $validator->errors()->add('paid_amount', 'The paid_amount must be greater than 0 and less than amount when payment_status is partial.');
            }
        });
    }
}
