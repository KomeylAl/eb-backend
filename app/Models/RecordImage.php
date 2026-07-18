<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class RecordImage extends Model
{
    use HasUuids;

    protected $fillable = [
        'medical_record_id',
        'file_path',
    ];

    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    public function getUrlAttribute(): ?string
    {
        return $this->file_path ? Storage::disk('public')->url($this->file_path) : null;
    }
}
