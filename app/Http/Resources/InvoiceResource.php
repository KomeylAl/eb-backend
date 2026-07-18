<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Invoice */
class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'admin_id' => $this->admin_id,
            'from_date' => $this->from_date?->toDateString(),
            'to_date' => $this->to_date?->toDateString(),
            'file_path' => $this->file_path,
            'file_url' => $this->file_url,
            'client' => ClientResource::make($this->whenLoaded('client')),
            'admin' => AdminResource::make($this->whenLoaded('admin')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
