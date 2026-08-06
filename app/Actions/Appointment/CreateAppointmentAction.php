<?php

namespace App\Actions\Appointment;

use App\Actions\Payment\LogPaymentTransactionAction;
use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentTransactionEvent;
use App\Models\Appointment;
use App\Models\Payment;
use App\Support\PaymentAmounts;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateAppointmentAction
{
    public function __construct(
        private LogPaymentTransactionAction $logPaymentTransaction,
    ) {}

    public function execute(array $data, ?string $actorId = null): Appointment
    {
        return DB::transaction(function () use ($data, $actorId) {
            $appointment = Appointment::query()->create([
                'date' => $data['date'],
                'time' => $data['time'],
                'amount' => $data['amount'],
                'service' => $data['service'] ?? null,
                'status' => AppointmentStatus::from($data['status']),
            ]);

            $now = now();

            DB::table('appointment_user')->insert([
                'id' => (string) Str::uuid(),
                'appointment_id' => $appointment->id,
                'doctor_id' => $data['doctor_id'],
                'client_id' => $data['client_id'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $paymentStatus = PaymentStatus::from($data['payment_status']);
            $amounts = PaymentAmounts::resolve(
                $paymentStatus,
                (int) $data['amount'],
                isset($data['paid_amount']) ? (int) $data['paid_amount'] : null,
            );

            $method = isset($data['payment_method'])
                ? PaymentMethod::from($data['payment_method'])
                : null;

            $payment = Payment::query()->create([
                'appointment_id' => $appointment->id,
                'status' => $paymentStatus,
                'amount' => $amounts['amount'],
                'paid_amount' => $amounts['paid_amount'],
                'method' => $method,
            ]);

            $this->logPaymentTransaction->execute(
                payment: $payment,
                event: PaymentTransactionEvent::Created,
                actorId: $actorId,
                newStatus: $paymentStatus->value,
                newPaidAmount: $amounts['paid_amount'],
                meta: [
                    'amount' => $amounts['amount'],
                    'method' => $method?->value,
                ],
            );

            return $appointment->load(['doctors', 'clients', 'payment']);
        });
    }
}
