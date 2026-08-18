<?php

namespace App\Http\Resources;

use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Appointment */
class PlusAppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $doctor = $this->relationLoaded('doctors') ? $this->doctors->first() : null;
        $client = $this->relationLoaded('clients') ? $this->clients->first() : null;

        return [
            'id' => $this->id,
            'treatment_program_id' => $this->treatment_program_id,
            'room_id' => $this->room_id,
            'date' => $this->date?->toDateString(),
            'time' => $this->time,
            'amount' => $this->amount,
            'service' => $this->service,
            'status' => $this->status?->value,
            'treatment_program' => TreatmentProgramResource::make($this->whenLoaded('treatmentProgram')),
            'room' => RoomResource::make($this->whenLoaded('room')),
            'doctor' => $doctor ? [
                'id' => $doctor->id,
                'name' => $doctor->name,
            ] : null,
            'client' => $client ? [
                'id' => $client->id,
                'name' => $client->name,
                'phone' => $client->phone,
            ] : null,
            'payment' => PaymentResource::make($this->whenLoaded('payment')),
            'homeworks' => HomeworkResource::collection($this->whenLoaded('homeworks')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
