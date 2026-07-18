<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Resume extends Model
{
    use HasUuids;

    protected $fillable = [
        'doctor_id',
        'title',
        'bio',
        'specialization',
        'educations',
        'experiences',
        'skills',
        'certifications',
        'social_links',
        'content',
        'file_path',
    ];

    protected function casts(): array
    {
        return [
            'educations' => 'array',
            'experiences' => 'array',
            'skills' => 'array',
            'certifications' => 'array',
            'social_links' => 'array',
        ];
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function getFileUrlAttribute(): ?string
    {
        return $this->file_path ? Storage::disk('public')->url($this->file_path) : null;
    }
}
