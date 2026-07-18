<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ClassDate */
class ClassDateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'class_id' => $this->class_id,
            'date' => $this->date?->toDateString(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
