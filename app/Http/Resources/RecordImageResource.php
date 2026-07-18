<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\RecordImage */
class RecordImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'medical_record_id' => $this->medical_record_id,
            'file_path' => $this->file_path,
            'url' => $this->url,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
