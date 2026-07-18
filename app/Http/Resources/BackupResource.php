<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Backup */
class BackupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'file_path' => $this->file_path,
            'file_url' => $this->file_url,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
