<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Appointment */
class AppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $doctor = $this->relationLoaded('doctors') ? $this->doctors->first() : null;
        $client = $this->relationLoaded('clients') ? $this->clients->first() : null;

        return [
            'id' => $this->id,
            'date' => $this->date?->toDateString(),
            'time' => $this->time,
            'amount' => $this->amount,
            'status' => $this->status?->value,
            'doctor' => $doctor ? [
                'id' => $doctor->id,
                'name' => $doctor->name,
                'phone' => $doctor->phone,
            ] : null,
            'client' => $client ? [
                'id' => $client->id,
                'name' => $client->name,
                'phone' => $client->phone,
            ] : null,
            'payment' => PaymentResource::make($this->whenLoaded('payment')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
