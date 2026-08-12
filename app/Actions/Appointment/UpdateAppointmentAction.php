<?php

namespace App\Actions\Appointment;

use App\Actions\Payment\LogPaymentTransactionAction;
use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentTransactionEvent;
use App\Models\Appointment;
use App\Support\PaymentAmounts;
use Illuminate\Support\Facades\DB;

class UpdateAppointmentAction
{
    public function __construct(
        private LogPaymentTransactionAction $logPaymentTransaction,
    ) {}

    public function execute(Appointment $appointment, array $data, ?string $actorId = null): Appointment
    {
        return DB::transaction(function () use ($appointment, $data, $actorId) {
            $appointment->update([
                'treatment_program_id' => $data['treatment_program_id'],
                'room_id' => $data['room_id'] ?? null,
                'date' => $data['date'],
                'time' => $data['time'],
                'amount' => $data['amount'],
                'service' => $data['service'] ?? null,
                'status' => AppointmentStatus::from($data['status']),
                'session_notes' => $data['session_notes'] ?? $appointment->session_notes,
            ]);

            DB::table('appointment_user')
                ->where('appointment_id', $appointment->id)
                ->update([
                    'doctor_id' => $data['doctor_id'],
                    'client_id' => $data['client_id'],
                    'updated_at' => now(),
                ]);

            $paymentStatus = PaymentStatus::from($data['payment_status']);
            $amounts = PaymentAmounts::resolve(
                $paymentStatus,
                (int) $data['amount'],
                isset($data['paid_amount']) ? (int) $data['paid_amount'] : null,
            );

            $method = array_key_exists('payment_method', $data)
                ? ($data['payment_method'] !== null ? PaymentMethod::from($data['payment_method']) : null)
                : null;

            $existing = $appointment->payment;
            $oldStatus = $existing?->status?->value;
            $oldPaidAmount = $existing?->paid_amount;
            $oldMethod = $existing?->method?->value;

            $paymentPayload = [
                'status' => $paymentStatus,
                'amount' => $amounts['amount'],
                'paid_amount' => $amounts['paid_amount'],
            ];

            if (array_key_exists('payment_method', $data)) {
                $paymentPayload['method'] = $method;
            }

            $payment = $appointment->payment()->updateOrCreate(
                ['appointment_id' => $appointment->id],
                $paymentPayload,
            );

            if ($oldStatus !== $paymentStatus->value || $oldPaidAmount !== $amounts['paid_amount']) {
                $this->logPaymentTransaction->execute(
                    payment: $payment,
                    event: $oldStatus !== $paymentStatus->value
                        ? PaymentTransactionEvent::StatusChanged
                        : PaymentTransactionEvent::AmountChanged,
                    actorId: $actorId,
                    oldStatus: $oldStatus,
                    newStatus: $paymentStatus->value,
                    oldPaidAmount: $oldPaidAmount,
                    newPaidAmount: $amounts['paid_amount'],
                    meta: [
                        'amount' => $amounts['amount'],
                    ],
                );
            }

            $newMethod = $payment->fresh()->method?->value;
            if (array_key_exists('payment_method', $data) && $oldMethod !== $newMethod) {
                $this->logPaymentTransaction->execute(
                    payment: $payment,
                    event: PaymentTransactionEvent::MethodChanged,
                    actorId: $actorId,
                    oldStatus: $paymentStatus->value,
                    newStatus: $paymentStatus->value,
                    oldPaidAmount: $amounts['paid_amount'],
                    newPaidAmount: $amounts['paid_amount'],
                    meta: [
                        'old_method' => $oldMethod,
                        'new_method' => $newMethod,
                    ],
                );
            }

            return $appointment->load(['doctors', 'clients', 'payment', 'treatmentProgram', 'room', 'homeworks']);
        });
    }
}
