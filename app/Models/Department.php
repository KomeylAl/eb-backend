<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Department extends Model
{
    use HasUuids;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'thumbnail',
        'content',
    ];

    public function doctors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'department_doctor', 'department_id', 'doctor_id')
            ->withTimestamps();
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->thumbnail ? Storage::disk('public')->url($this->thumbnail) : null;
    }
}
