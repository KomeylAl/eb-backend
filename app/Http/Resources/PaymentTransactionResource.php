<?php

namespace App\Http\Resources;

use App\Models\PaymentTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PaymentTransaction */
class PaymentTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_id' => $this->payment_id,
            'actor_id' => $this->actor_id,
            'event' => $this->event?->value,
            'old_status' => $this->old_status,
            'new_status' => $this->new_status,
            'old_paid_amount' => $this->old_paid_amount,
            'new_paid_amount' => $this->new_paid_amount,
            'meta' => $this->meta,
            'actor' => AdminResource::make($this->whenLoaded('actor')),
            'payment' => PaymentResource::make($this->whenLoaded('payment')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
