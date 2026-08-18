<?php

namespace App\Models;

use App\Enums\CertificateTemplateKey;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class WorkshopCertificateTemplate extends Model
{
    use HasUuids;

    protected $fillable = [
        'workshop_id',
        'template_key',
        'clinic_name',
        'title',
        'body_text',
        'footer_text',
        'signer_name',
        'signer_title',
        'logo_path',
        'signature_path',
    ];

    protected function casts(): array
    {
        return [
            'template_key' => CertificateTemplateKey::class,
        ];
    }

    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }

    public function assetsDisk(): string
    {
        return (string) (config('media.collections.workshop_certificate_assets.disk') ?? 'public');
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? Storage::disk($this->assetsDisk())->url($this->logo_path) : null;
    }

    public function getSignatureUrlAttribute(): ?string
    {
        return $this->signature_path ? Storage::disk($this->assetsDisk())->url($this->signature_path) : null;
    }

    public function deleteAsset(?string $path): void
    {
        if ($path && Storage::disk($this->assetsDisk())->exists($path)) {
            Storage::disk($this->assetsDisk())->delete($path);
        }
    }
}
