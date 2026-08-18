<?php

namespace App\Models;

use App\Enums\ResourceType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class WorkshopMaterial extends Model
{
    use HasUuids;

    protected $fillable = [
        'workshop_id',
        'title',
        'type',
        'description',
        'link',
        'file_path',
        'original_name',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'type' => ResourceType::class,
            'sort_order' => 'integer',
        ];
    }

    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }

    public function disk(): string
    {
        return (string) (config('media.collections.workshop_materials.disk') ?? 'local');
    }

    public function getFileUrlAttribute(): ?string
    {
        // Private materials are not publicly URL-addressable; use download endpoint.
        return null;
    }

    public function deleteFile(): void
    {
        if ($this->file_path && Storage::disk($this->disk())->exists($this->file_path)) {
            Storage::disk($this->disk())->delete($this->file_path);
        }
    }
}
