<?php

namespace App\Http\Resources;

use App\Models\Homework;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Homework */
class HomeworkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'appointment_id' => $this->appointment_id,
            'type' => $this->type?->value,
            'title' => $this->title,
            'body' => $this->body,
            'meta' => $this->meta,
            'status' => $this->status?->value,
            'due_at' => $this->due_at,
            'completed_at' => $this->completed_at,
            'completed_by' => $this->completed_by,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
