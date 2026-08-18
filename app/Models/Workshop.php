<?php

namespace App\Models;

use App\Enums\WorkshopType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Storage;

class Workshop extends Model
{
    use HasUuids;

    protected $fillable = [
        'title',
        'slug',
        'type',
        'excerpt',
        'content',
        'organizers',
        'start_date',
        'end_date',
        'week_day',
        'time',
        'img_path',
    ];

    protected function casts(): array
    {
        return [
            'type' => WorkshopType::class,
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(WorkshopSession::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(WorkshopMaterial::class);
    }

    public function certificateTemplate(): HasOne
    {
        return $this->hasOne(WorkshopCertificateTemplate::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(WorkshopCertificate::class);
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(Participant::class, 'participant_workshop')
            ->withPivot(['registered_at', 'approved', 'joined_at'])
            ->withTimestamps();
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->img_path ? Storage::disk('public')->url($this->img_path) : null;
    }
}
