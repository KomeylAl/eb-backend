<?php

namespace App\Http\Resources;

use App\Models\FinancialAdjustment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin FinancialAdjustment */
class FinancialAdjustmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'admin_id' => $this->admin_id,
            'appointment_id' => $this->appointment_id,
            'invoice_id' => $this->invoice_id,
            'type' => $this->type?->value,
            'amount' => $this->amount,
            'reason' => $this->reason,
            'status' => $this->status?->value,
            'client' => ClientResource::make($this->whenLoaded('client')),
            'admin' => AdminResource::make($this->whenLoaded('admin')),
            'appointment' => AppointmentResource::make($this->whenLoaded('appointment')),
            'invoice' => InvoiceResource::make($this->whenLoaded('invoice')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
