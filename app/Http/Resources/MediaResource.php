<?php

namespace App\Http\Resources;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Media */
class MediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'disk' => $this->disk,
            'path' => $this->path,
            'collection' => $this->collection,
            'folder_id' => $this->folder_id,
            'original_name' => $this->original_name,
            'name' => $this->name,
            'mime' => $this->mime,
            'size' => $this->size,
            'visibility' => $this->visibility,
            'url' => $this->url,
            'is_image' => $this->is_image,
            'uploaded_by' => $this->uploaded_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
