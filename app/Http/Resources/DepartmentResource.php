<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Department */
class DepartmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'thumbnail' => $this->thumbnail,
            'thumbnail_url' => $this->thumbnail_url,
            'content' => $this->content,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
