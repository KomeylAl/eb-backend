<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class ClientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'birth_date' => $this->birth_date?->toDateString(),
            'address' => $this->address,
            'type' => $this->type?->value,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
