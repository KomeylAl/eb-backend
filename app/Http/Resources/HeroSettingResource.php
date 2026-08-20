<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\HeroSetting */
class HeroSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'background' => $this->background,
            'background_url' => $this->background_url,
            'autoplay_ms' => $this->autoplay_ms,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
