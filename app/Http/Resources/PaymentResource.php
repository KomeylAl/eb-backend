<?php

namespace App\Http\Resources;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Payment */
class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'appointment_id' => $this->appointment_id,
            'status' => $this->status?->value,
            'amount' => $this->amount,
            'paid_amount' => $this->paid_amount,
            'method' => $this->method?->value,
            'appointment' => AppointmentResource::make($this->whenLoaded('appointment')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
