<?php

namespace App\Models;

use App\Enums\CertificateSource;
use App\Enums\CertificateTemplateKey;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class WorkshopCertificate extends Model
{
    use HasUuids;

    protected $fillable = [
        'workshop_id',
        'participant_id',
        'source',
        'template_key',
        'certificate_number',
        'issued_at',
        'payload',
        'file_path',
        'original_name',
    ];

    protected function casts(): array
    {
        return [
            'source' => CertificateSource::class,
            'template_key' => CertificateTemplateKey::class,
            'issued_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function disk(): string
    {
        return (string) (config('media.collections.workshop_certificates.disk') ?? 'local');
    }

    public function hasFile(): bool
    {
        return filled($this->file_path);
    }

    public function deleteFile(): void
    {
        if ($this->file_path && Storage::disk($this->disk())->exists($this->file_path)) {
            Storage::disk($this->disk())->delete($this->file_path);
        }
    }
}
