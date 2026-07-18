<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class About extends Model
{
    use HasUuids;

    protected $fillable = [
        'title',
        'about',
        'phones',
        'mobile_phones',
        'address',
        'logo',
        'latitude',
        'longitude',
    ];

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? Storage::disk('public')->url($this->logo) : null;
    }
}
