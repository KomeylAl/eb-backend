<?php

namespace App\Models;

use App\Enums\ResourceType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class DoctorResource extends Model
{
    use HasUuids;

    protected $fillable = [
        'doctor_id',
        'title',
        'type',
        'description',
        'link',
        'file_path',
    ];

    protected function casts(): array
    {
        return [
            'type' => ResourceType::class,
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
