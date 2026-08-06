<?php

namespace App\Actions\Invoice;

use App\Models\Appointment;
use Illuminate\Support\Collection;

class SuggestInvoiceItemsAction
{
    /**
     * Build suggested invoice line items from a client's appointments in a date range.
     * Frontend may edit/add/remove before saving the invoice.
     *
     * @return list<array{
     *     appointment_id: string,
     *     description: string,
     *     unit: string,
     *     quantity: int,
     *     unit_price: int,
     *     line_total: int,
     *     date: ?string,
     *     time: ?string,
     *     doctor_name: ?string,
     *     payment_status: ?string
     * }>
     */
    public function execute(string $clientId, string $fromDate, string $toDate): array
    {
        /** @var Collection<int, Appointment> $appointments */
        $appointments = Appointment::query()
            ->with(['doctors', 'payment'])
            ->whereBetween('date', [$fromDate, $toDate])
            ->whereHas('clients', fn ($q) => $q->where('users.id', $clientId))
            ->orderBy('date')
            ->orderBy('time')
            ->get();

        return $appointments->values()->map(function (Appointment $appointment, int $index) {
            $doctor = $appointment->doctors->first();
            $service = $appointment->service ?: 'نوبت مشاوره';
            $doctorName = $doctor?->name;
            $description = $doctorName
                ? trim($service.' - '.$doctorName)
                : $service;

            $unitPrice = (int) $appointment->amount;

            return [
                'appointment_id' => $appointment->id,
                'description' => $description,
                'unit' => 'جلسه',
                'quantity' => 1,
                'unit_price' => $unitPrice,
                'line_total' => $unitPrice,
                'date' => $appointment->date?->toDateString(),
                'time' => $appointment->time,
                'doctor_name' => $doctorName,
                'payment_status' => $appointment->payment?->status?->value,
                'sort_order' => $index,
            ];
        })->all();
    }
}
