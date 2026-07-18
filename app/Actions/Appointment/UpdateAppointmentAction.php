<?php

namespace App\Actions\Appointment;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentStatus;
use App\Models\Appointment;
use Illuminate\Support\Facades\DB;

class UpdateAppointmentAction
{
    public function execute(Appointment $appointment, array $data): Appointment
    {
        return DB::transaction(function () use ($appointment, $data) {
            $appointment->update([
                'date' => $data['date'],
                'time' => $data['time'],
                'amount' => $data['amount'],
                'status' => AppointmentStatus::from($data['status']),
            ]);

            DB::table('appointment_user')
                ->where('appointment_id', $appointment->id)
                ->update([
                    'doctor_id' => $data['doctor_id'],
                    'client_id' => $data['client_id'],
                    'updated_at' => now(),
                ]);

            $paymentStatus = PaymentStatus::from($data['payment_status']);
            $paymentAmount = $paymentStatus === PaymentStatus::Paid
                ? (int) $data['amount']
                : 0;

            $appointment->payment()->updateOrCreate(
                ['appointment_id' => $appointment->id],
                [
                    'status' => $paymentStatus,
                    'amount' => $paymentAmount,
                ],
            );

            return $appointment->load(['doctors', 'clients', 'payment']);
        });
    }
}
