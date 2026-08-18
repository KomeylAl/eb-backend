<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\WorkshopMaterial */
class WorkshopMaterialResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workshop_id' => $this->workshop_id,
            'title' => $this->title,
            'type' => $this->type?->value,
            'description' => $this->description,
            'link' => $this->link,
            'file_path' => $this->file_path,
            'original_name' => $this->original_name,
            'sort_order' => $this->sort_order,
            'has_file' => filled($this->file_path),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
