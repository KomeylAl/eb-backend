<?php

namespace App\Models;

use App\Enums\Gender;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Participant extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'english_name',
        'phone',
        'national_code',
        'gender',
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
}
