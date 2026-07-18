<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class DoctorProfile extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'national_code',
        'card_number',
        'medical_number',
        'avatar',
        'days',
        'times',
        'profile_path',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'days' => 'array',
            'times' => 'array',
            'sort_order' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar ? Storage::disk('public')->url($this->avatar) : null;
    }
}
