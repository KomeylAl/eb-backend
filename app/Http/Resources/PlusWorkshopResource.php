<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Workshop */
class PlusWorkshopResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'type' => $this->type?->value,
            'type_label' => $this->type?->labelFa(),
            'excerpt' => $this->excerpt,
            'organizers' => $this->organizers,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'week_day' => $this->week_day,
            'time' => $this->time,
            'image_url' => $this->image_url,
            'joined_at' => $this->whenPivotLoaded('participant_workshop', fn () => $this->pivot->joined_at),
            'materials_count' => $this->whenCounted('materials'),
            'has_certificate' => $this->when(
                isset($this->has_certificate),
                fn () => (bool) $this->has_certificate,
            ),
        ];
    }
}
