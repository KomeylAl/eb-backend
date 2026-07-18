<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\About */
class AboutResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'about' => $this->about,
            'phones' => $this->phones,
            'mobile_phones' => $this->mobile_phones,
            'address' => $this->address,
            'logo' => $this->logo,
            'logo_url' => $this->logo_url,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
