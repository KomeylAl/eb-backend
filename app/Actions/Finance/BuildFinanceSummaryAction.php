<?php

namespace App\Actions\Finance;

use App\Enums\PaymentStatus;
use App\Models\Appointment;
use App\Models\Payment;

class BuildFinanceSummaryAction
{
    /**
     * @return array<string, mixed>
     */
    public function execute(?string $from = null, ?string $to = null, ?string $doctorId = null): array
    {
        $paymentQuery = Payment::query()->with(['appointment.doctors']);

        if ($from) {
            $paymentQuery->whereHas('appointment', fn ($q) => $q->whereDate('date', '>=', $from));
        }

        if ($to) {
            $paymentQuery->whereHas('appointment', fn ($q) => $q->whereDate('date', '<=', $to));
        }

        if ($doctorId) {
            $paymentQuery->whereHas('appointment.doctors', fn ($q) => $q->where('users.id', $doctorId));
        }

        $payments = $paymentQuery->get();

        $billed = (int) $payments->sum('amount');
        $paid = (int) $payments->sum('paid_amount');

        $byStatus = [];
        foreach (PaymentStatus::cases() as $status) {
            $group = $payments->where('status', $status);
            $byStatus[$status->value] = [
                'count' => $group->count(),
                'billed' => (int) $group->sum('amount'),
                'paid' => (int) $group->sum('paid_amount'),
            ];
        }

        return [
            'from' => $from,
            'to' => $to,
            'doctor_id' => $doctorId,
            'totals' => [
                'appointments' => $payments->count(),
                'billed' => $billed,
                'paid' => $paid,
                'outstanding' => max(0, $billed - $paid),
            ],
            'by_status' => $byStatus,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function byDoctor(?string $from = null, ?string $to = null): array
    {
        $query = Appointment::query()->with(['doctors', 'payment']);

        if ($from) {
            $query->whereDate('date', '>=', $from);
        }
        if ($to) {
            $query->whereDate('date', '<=', $to);
        }

        $appointments = $query->get();

        $grouped = [];

        foreach ($appointments as $appointment) {
            $doctor = $appointment->doctors->first();
            $key = $doctor?->id ?? 'unknown';

            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'doctor_id' => $doctor?->id,
                    'doctor_name' => $doctor?->name,
                    'appointments' => 0,
                    'billed' => 0,
                    'paid' => 0,
                ];
            }

            $grouped[$key]['appointments']++;
            $grouped[$key]['billed'] += (int) ($appointment->payment?->amount ?? $appointment->amount);
            $grouped[$key]['paid'] += (int) ($appointment->payment?->paid_amount ?? 0);
        }

        return array_values($grouped);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function byDay(?string $from = null, ?string $to = null, ?string $doctorId = null): array
    {
        $query = Appointment::query()->with(['payment', 'doctors']);

        if ($from) {
            $query->whereDate('date', '>=', $from);
        }
        if ($to) {
            $query->whereDate('date', '<=', $to);
        }
        if ($doctorId) {
            $query->whereHas('doctors', fn ($q) => $q->where('users.id', $doctorId));
        }

        $appointments = $query->orderBy('date')->get();

        $grouped = [];

        foreach ($appointments as $appointment) {
            $day = $appointment->date?->toDateString() ?? 'unknown';

            if (! isset($grouped[$day])) {
                $grouped[$day] = [
                    'date' => $day,
                    'appointments' => 0,
                    'billed' => 0,
                    'paid' => 0,
                ];
            }

            $grouped[$day]['appointments']++;
            $grouped[$day]['billed'] += (int) ($appointment->payment?->amount ?? $appointment->amount);
            $grouped[$day]['paid'] += (int) ($appointment->payment?->paid_amount ?? 0);
        }

        return array_values($grouped);
    }

    /**
     * @return array<string, mixed>
     */
    public function compare(
        string $from,
        string $to,
        string $compareFrom,
        string $compareTo,
        ?string $doctorId = null,
    ): array {
        $current = $this->execute($from, $to, $doctorId);
        $previous = $this->execute($compareFrom, $compareTo, $doctorId);

        $growth = function (int $currentValue, int $previousValue): ?float {
            if ($previousValue === 0) {
                return $currentValue === 0 ? 0.0 : null;
            }

            return round((($currentValue - $previousValue) / $previousValue) * 100, 2);
        };

        return [
            'current' => $current,
            'previous' => $previous,
            'growth' => [
                'billed_percent' => $growth($current['totals']['billed'], $previous['totals']['billed']),
                'paid_percent' => $growth($current['totals']['paid'], $previous['totals']['paid']),
                'appointments_percent' => $growth($current['totals']['appointments'], $previous['totals']['appointments']),
            ],
        ];
    }
}
