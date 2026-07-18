<?php

namespace App\Models;

use App\Enums\AssessmentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class InitAssessment extends Model
{
    use HasUuids;

    protected $fillable = [
        'date',
        'time',
        'status',
        'file_path',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'status' => AssessmentStatus::class,
        ];
    }

    public function doctors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'assessment_user', 'init_assessment_id', 'doctor_id')
            ->withPivot('client_id')
            ->withTimestamps();
    }

    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'assessment_user', 'init_assessment_id', 'client_id')
            ->withPivot('doctor_id')
            ->withTimestamps();
    }

    public function getFileUrlAttribute(): ?string
    {
        return $this->file_path ? Storage::disk('public')->url($this->file_path) : null;
    }
}
