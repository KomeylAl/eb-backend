<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\DoctorProfile */
class DoctorProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'national_code' => $this->national_code,
            'card_number' => $this->card_number,
            'medical_number' => $this->medical_number,
            'avatar' => $this->avatar,
            'avatar_url' => $this->avatar_url,
            'days' => $this->days,
            'times' => $this->times,
            'profile_path' => $this->profile_path,
            'sort_order' => $this->sort_order,
        ];
    }
}
