<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Tag extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'slug',
        'excerpt',
        'content',
        'image',
    ];

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_tag')->withTimestamps();
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? Storage::disk('public')->url($this->image) : null;
    }
}
