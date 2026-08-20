<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class HeroSetting extends Model
{
    use HasUuids;

    protected $fillable = [
        'background',
        'autoplay_ms',
    ];

    protected function casts(): array
    {
        return [
            'autoplay_ms' => 'integer',
        ];
    }

    public function getBackgroundUrlAttribute(): ?string
    {
        return $this->background
            ? Storage::disk('public')->url($this->background)
            : null;
    }
}
