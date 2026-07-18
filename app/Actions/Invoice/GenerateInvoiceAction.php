<?php

namespace App\Actions\Invoice;

use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class GenerateInvoiceAction
{
    /**
     * @param  array{client_id: string, from_date: string, to_date: string, admin_id: string}  $data
     */
    public function execute(array $data): Invoice
    {
        $client = User::query()->findOrFail($data['client_id']);

        $appointments = Appointment::query()
            ->with(['doctors', 'payment'])
            ->whereBetween('date', [$data['from_date'], $data['to_date']])
            ->whereHas('clients', fn ($q) => $q->where('users.id', $client->id))
            ->orderBy('date')
            ->get();

        $items = $appointments->map(function (Appointment $appointment) {
            $doctor = $appointment->doctors->first();

            return [
                'id' => $appointment->id,
                'doctor' => $doctor?->name,
                'doctor_id' => $doctor?->id,
                'date' => $appointment->date?->toDateString(),
                'time' => $appointment->time,
                'amount' => $appointment->amount,
                'status' => $appointment->status?->value,
                'payment_status' => $appointment->payment?->status?->value,
            ];
        })->values()->all();

        $total = collect($items)->sum('amount');

        $summary = [
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'phone' => $client->phone,
            ],
            'from_date' => $data['from_date'],
            'to_date' => $data['to_date'],
            'generated_at' => now()->toIso8601String(),
            'total' => $total,
            'appointments' => $items,
        ];

        $fileName = 'invoices/invoice_'.now()->format('Y-m-d_H-i-s').'_'.$client->id.'.json';
        Storage::disk('public')->put(
            $fileName,
            json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        );

        return Invoice::query()->create([
            'client_id' => $client->id,
            'admin_id' => $data['admin_id'],
            'from_date' => $data['from_date'],
            'to_date' => $data['to_date'],
            'file_path' => $fileName,
        ])->load(['client', 'admin']);
    }
}
