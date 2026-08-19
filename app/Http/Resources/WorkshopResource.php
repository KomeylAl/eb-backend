<?php

namespace App\Http\Resources;

use App\Models\Workshop;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Workshop */
class WorkshopResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'type' => $this->type?->value,
            'excerpt' => $this->excerpt,
            'content' => $this->content,
            'organizers' => $this->organizers,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'week_day' => $this->week_day,
            'time' => $this->time,
            'img_path' => $this->img_path,
            'image_url' => $this->image_url,
            'registration_open' => (bool) $this->registration_open,
            'registration_available' => $this->isRegistrationAvailable(),
            'sessions' => WorkshopSessionResource::collection($this->whenLoaded('sessions')),
            'participants' => ParticipantResource::collection($this->whenLoaded('participants')),
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
            'comments_count' => $this->when(isset($this->comments_count), $this->comments_count),
            'rating_avg' => $this->when(isset($this->rating_avg), $this->rating_avg !== null ? round((float) $this->rating_avg, 2) : null),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
