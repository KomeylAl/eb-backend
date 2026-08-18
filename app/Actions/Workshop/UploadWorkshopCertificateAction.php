<?php

namespace App\Actions\Workshop;

use App\Enums\CertificateSource;
use App\Enums\CertificateTemplateKey;
use App\Models\Participant;
use App\Models\Workshop;
use App\Models\WorkshopCertificate;
use App\Services\FileService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UploadWorkshopCertificateAction
{
    public function __construct(private FileService $files) {}

    public function execute(
        Workshop $workshop,
        string $participantId,
        UploadedFile $file,
        ?string $certificateNumber = null,
        ?string $uploadedBy = null,
    ): WorkshopCertificate {
        $participant = $workshop->participants()
            ->wherePivot('approved', true)
            ->where('participants.id', $participantId)
            ->first();

        if (! $participant instanceof Participant) {
            throw ValidationException::withMessages([
                'participant_id' => ['فقط برای شرکت‌کننده تأییدشده می‌توان فایل مدرک آپلود کرد.'],
            ]);
        }

        $certificate = WorkshopCertificate::query()
            ->where('workshop_id', $workshop->id)
            ->where('participant_id', $participant->id)
            ->first();

        if ($certificate?->file_path) {
            $certificate->deleteFile();
        }

        $media = $this->files->store(
            $file,
            'workshop_certificates',
            context: [
                'workshop_slug' => $workshop->slug,
                'participant_id' => $participant->id,
            ],
            uploadedBy: $uploadedBy,
        );

        $number = $certificateNumber
            ?: $certificate?->certificate_number
            ?: $this->uniqueNumber();

        if ($certificate) {
            $certificate->update([
                'source' => CertificateSource::Uploaded,
                'template_key' => CertificateTemplateKey::Uploaded,
                'file_path' => $media->path,
                'original_name' => $file->getClientOriginalName(),
                'certificate_number' => $number,
                'issued_at' => now(),
            ]);

            return $certificate->fresh()->load('participant');
        }

        return WorkshopCertificate::query()->create([
            'workshop_id' => $workshop->id,
            'participant_id' => $participant->id,
            'source' => CertificateSource::Uploaded,
            'template_key' => CertificateTemplateKey::Uploaded,
            'certificate_number' => $number,
            'issued_at' => now(),
            'payload' => null,
            'file_path' => $media->path,
            'original_name' => $file->getClientOriginalName(),
        ])->load('participant');
    }

    private function uniqueNumber(): string
    {
        do {
            $number = 'EBZ-'.now()->format('Y').'-'.Str::upper(Str::random(6));
        } while (WorkshopCertificate::query()->where('certificate_number', $number)->exists());

        return $number;
    }
}
