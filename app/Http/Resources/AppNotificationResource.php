<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\AppNotification */
class AppNotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'notifiable_type' => $this->notifiable_type,
            'notifiable_id' => $this->notifiable_id,
            'priority' => $this->priority,
            'delivery_channels' => $this->delivery_channels,
            'meta' => $this->meta,
            'status' => $this->status,
            'scheduled_at' => $this->scheduled_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
