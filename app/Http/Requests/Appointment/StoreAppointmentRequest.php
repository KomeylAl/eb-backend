<?php

namespace App\Http\Requests\Appointment;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserType;
use App\Support\DoctorUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'status' => ['required', 'string', Rule::enum(AppointmentStatus::class)],
            'payment_status' => ['required', 'string', Rule::enum(PaymentStatus::class)],
        ];
    }
}
