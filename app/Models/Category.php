<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Category extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'slug',
        'excerpt',
        'content',
        'image',
    ];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? Storage::disk('public')->url($this->image) : null;
    }
}
