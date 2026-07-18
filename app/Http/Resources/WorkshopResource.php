<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Workshop */
class WorkshopResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content' => $this->content,
            'organizers' => $this->organizers,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'week_day' => $this->week_day,
            'time' => $this->time,
            'img_path' => $this->img_path,
            'image_url' => $this->image_url,
            'sessions' => WorkshopSessionResource::collection($this->whenLoaded('sessions')),
            'participants' => ParticipantResource::collection($this->whenLoaded('participants')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
