<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\WorkshopSession */
class WorkshopSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workshop_id' => $this->workshop_id,
            'title' => $this->title,
            'description' => $this->description,
            'session_date' => $this->session_date?->toDateString(),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'location' => $this->location,
            'link' => $this->link,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
