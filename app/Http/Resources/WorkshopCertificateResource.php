<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\WorkshopCertificate */
class WorkshopCertificateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workshop_id' => $this->workshop_id,
            'participant_id' => $this->participant_id,
            'source' => $this->source?->value ?? 'generated',
            'source_label' => $this->source?->labelFa(),
            'template_key' => $this->template_key?->value,
            'certificate_number' => $this->certificate_number,
            'issued_at' => $this->issued_at,
            'payload' => $this->payload,
            'has_file' => $this->hasFile(),
            'original_name' => $this->original_name,
            'participant' => ParticipantResource::make($this->whenLoaded('participant')),
            'workshop' => $this->whenLoaded('workshop', fn () => [
                'id' => $this->workshop->id,
                'title' => $this->workshop->title,
                'type' => $this->workshop->type?->value,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
