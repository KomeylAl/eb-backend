<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\DoctorResource */
class DoctorResourceItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'doctor_id' => $this->doctor_id,
            'title' => $this->title,
            'type' => $this->type?->value,
            'description' => $this->description,
            'link' => $this->link,
            'file_path' => $this->file_path,
            'file_url' => $this->file_url,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
