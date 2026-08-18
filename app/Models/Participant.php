<?php

namespace App\Models;

use App\Enums\Gender;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Participant extends Authenticatable
{
    use HasApiTokens, HasUuids;

    protected $fillable = [
        'name',
        'english_name',
        'phone',
        'national_code',
        'gender',
    ];

    protected $hidden = [
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
        ];
    }

    public function workshops(): BelongsToMany
    {
        return $this->belongsToMany(Workshop::class, 'participant_workshop')
            ->withPivot(['registered_at', 'approved', 'joined_at'])
            ->withTimestamps();
    }

    public function approvedWorkshops(): BelongsToMany
    {
        return $this->workshops()->wherePivot('approved', true);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(WorkshopCertificate::class);
    }

    public function isApprovedFor(Workshop|string $workshop): bool
    {
        $workshopId = $workshop instanceof Workshop ? $workshop->id : $workshop;

        return $this->workshops()
            ->where('workshops.id', $workshopId)
            ->wherePivot('approved', true)
            ->exists();
    }
}
