<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\InitAssessment */
class InitAssessmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $doctor = $this->relationLoaded('doctors') ? $this->doctors->first() : null;
        $client = $this->relationLoaded('clients') ? $this->clients->first() : null;

        return [
            'id' => $this->id,
            'date' => $this->date?->toDateString(),
            'time' => $this->time,
            'status' => $this->status?->value,
            'file_path' => $this->file_path,
            'file_url' => $this->file_url,
            'doctor' => $doctor ? [
                'id' => $doctor->id,
                'name' => $doctor->name,
                'phone' => $doctor->phone,
            ] : null,
            'client' => $client ? [
                'id' => $client->id,
                'name' => $client->name,
                'phone' => $client->phone,
            ] : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
