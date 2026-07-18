<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Participant */
class ParticipantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'english_name' => $this->english_name,
            'phone' => $this->phone,
            'national_code' => $this->national_code,
            'gender' => $this->gender?->value,
            'approved' => $this->whenPivotLoaded('participant_workshop', fn () => (bool) $this->pivot->approved),
            'registered_at' => $this->whenPivotLoaded('participant_workshop', fn () => $this->pivot->registered_at),
            'joined_at' => $this->whenPivotLoaded('participant_workshop', fn () => $this->pivot->joined_at),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
