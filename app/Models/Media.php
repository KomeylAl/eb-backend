<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class Media extends Model
{
    use HasUuids;

    protected $fillable = [
        'disk',
        'path',
        'collection',
        'folder_id',
        'original_name',
        'name',
        'mime',
        'size',
        'visibility',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'folder_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isPublic(): bool
    {
        return $this->visibility === 'public' && $this->disk === 'public';
    }

    public function getUrlAttribute(): ?string
    {
        if ($this->isPublic()) {
            return Storage::disk($this->disk)->url($this->path);
        }

        return URL::temporarySignedRoute(
            'media.file',
            now()->addMinutes(60),
            ['media' => $this->id],
        );
    }

    public function getIsImageAttribute(): bool
    {
        return is_string($this->mime) && str_starts_with($this->mime, 'image/');
    }
}
