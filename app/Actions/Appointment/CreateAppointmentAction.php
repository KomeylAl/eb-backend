<?php

namespace App\Actions\Appointment;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentStatus;
use App\Models\Appointment;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateAppointmentAction
{
    public function execute(array $data): Appointment
    {
        return DB::transaction(function () use ($data) {
            $appointment = Appointment::query()->create([
                'date' => $data['date'],
                'time' => $data['time'],
                'amount' => $data['amount'],
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
            $paymentAmount = $paymentStatus === PaymentStatus::Paid
                ? (int) $data['amount']
                : 0;

            Payment::query()->create([
                'appointment_id' => $appointment->id,
                'status' => $paymentStatus,
                'amount' => $paymentAmount,
            ]);

            return $appointment->load(['doctors', 'clients', 'payment']);
        });
    }
}
