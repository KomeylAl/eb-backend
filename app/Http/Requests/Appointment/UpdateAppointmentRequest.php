<?php

namespace App\Http\Requests\Appointment;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserType;
use App\Models\Appointment;
use App\Models\TreatmentProgram;
use App\Support\DoctorUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'treatment_program_id' => [
                'required',
                'uuid',
                'exists:treatment_programs,id',
            ],
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
            'room_id' => ['nullable', 'uuid', 'exists:rooms,id'],
            'date' => ['required', 'date'],
            'time' => ['required', 'string', 'max:20'],
            'amount' => ['required', 'integer', 'min:0'],
            'service' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', Rule::enum(AppointmentStatus::class)],
            'session_notes' => ['nullable', 'string'],
            'payment_status' => ['required', 'string', Rule::enum(PaymentStatus::class)],
            'paid_amount' => ['nullable', 'integer', 'min:0'],
            'payment_method' => ['nullable', 'string', Rule::enum(PaymentMethod::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('payment_status') === PaymentStatus::Partial->value) {
                $amount = (int) $this->input('amount');
                $paidAmount = $this->input('paid_amount');

                if ($paidAmount === null) {
                    $validator->errors()->add('paid_amount', 'The paid_amount field is required when payment_status is partial.');
                } else {
                    $paidAmount = (int) $paidAmount;
                    if ($paidAmount <= 0 || $paidAmount >= $amount) {
                        $validator->errors()->add('paid_amount', 'The paid_amount must be greater than 0 and less than amount when payment_status is partial.');
                    }
                }
            }

            $program = TreatmentProgram::query()->find($this->input('treatment_program_id'));
            if ($program && (
                $program->client_id !== $this->input('client_id')
                || $program->doctor_id !== $this->input('doctor_id')
            )) {
                $validator->errors()->add(
                    'treatment_program_id',
                    'Treatment program must belong to the selected client and doctor.',
                );
            }

            $roomId = $this->input('room_id');
            /** @var Appointment|null $appointment */
            $appointment = $this->route('appointment');
            if ($roomId && $this->input('date') && $this->input('time')) {
                $conflict = Appointment::query()
                    ->where('room_id', $roomId)
                    ->whereDate('date', $this->input('date'))
                    ->where('time', $this->input('time'))
                    ->where('status', AppointmentStatus::Pending->value)
                    ->when($appointment, fn ($q) => $q->where('id', '!=', $appointment->id))
                    ->exists();

                if ($conflict) {
                    $validator->errors()->add('room_id', 'This room is already booked for the selected date and time.');
                }
            }
        });
    }
}
