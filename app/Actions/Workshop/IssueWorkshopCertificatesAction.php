<?php

namespace App\Actions\Workshop;

use App\Enums\CertificateTemplateKey;
use App\Enums\WorkshopType;
use App\Models\Participant;
use App\Models\Workshop;
use App\Models\WorkshopCertificate;
use App\Models\WorkshopCertificateTemplate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class IssueWorkshopCertificatesAction
{
    /**
     * @param  list<string>  $participantIds
     * @return list<WorkshopCertificate>
     */
    public function execute(Workshop $workshop, array $participantIds): array
    {
        $template = $workshop->certificateTemplate;
        if (! $template) {
            throw ValidationException::withMessages([
                'template' => ['ابتدا قالب گواهی این کارگاه را ذخیره کنید.'],
            ]);
        }

        $ids = array_values(array_unique($participantIds));
        if ($ids === []) {
            throw ValidationException::withMessages([
                'participant_ids' => ['حداقل یک شرکت‌کننده را انتخاب کنید.'],
            ]);
        }

        $approved = $workshop->participants()
            ->wherePivot('approved', true)
            ->whereIn('participants.id', $ids)
            ->get()
            ->keyBy('id');

        $missing = array_values(array_diff($ids, $approved->keys()->all()));
        if ($missing !== []) {
            throw ValidationException::withMessages([
                'participant_ids' => ['فقط شرکت‌کنندگان تأییدشده می‌توانند گواهی بگیرند.'],
            ]);
        }

        $issued = [];
        foreach ($approved as $participant) {
            $existing = WorkshopCertificate::query()
                ->where('workshop_id', $workshop->id)
                ->where('participant_id', $participant->id)
                ->first();

            if ($existing) {
                $issued[] = $existing->load('participant');

                continue;
            }

            $number = $this->uniqueNumber();
            $payload = $this->buildPayload($workshop, $template, $participant, $number);

            $certificate = WorkshopCertificate::query()->create([
                'workshop_id' => $workshop->id,
                'participant_id' => $participant->id,
                'source' => \App\Enums\CertificateSource::Generated,
                'template_key' => $template->template_key,
                'certificate_number' => $number,
                'issued_at' => now(),
                'payload' => $payload,
            ]);

            $issued[] = $certificate->load('participant');
        }

        return $issued;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(
        Workshop $workshop,
        WorkshopCertificateTemplate $template,
        Participant $participant,
        string $certificateNumber,
    ): array {
        $issueDate = now()->toDateString();
        $placeholders = [
            'participant_name' => (string) $participant->name,
            'english_name' => (string) ($participant->english_name ?? ''),
            'national_code' => (string) ($participant->national_code ?? ''),
            'phone' => (string) ($participant->phone ?? ''),
            'workshop_title' => (string) $workshop->title,
            'workshop_type' => $workshop->type instanceof WorkshopType
                ? $workshop->type->labelFa()
                : (string) $workshop->type,
            'start_date' => optional($workshop->start_date)->toDateString() ?? '',
            'end_date' => optional($workshop->end_date)->toDateString() ?? '',
            'issue_date' => $issueDate,
            'certificate_number' => $certificateNumber,
            'clinic_name' => (string) ($template->clinic_name ?? ''),
        ];

        $body = $this->replacePlaceholders((string) ($template->body_text ?? ''), $placeholders);

        return [
            'template_key' => $template->template_key instanceof CertificateTemplateKey
                ? $template->template_key->value
                : (string) $template->template_key,
            'clinic_name' => $template->clinic_name,
            'title' => $template->title,
            'body_text' => $template->body_text,
            'body_rendered' => $body,
            'footer_text' => $template->footer_text,
            'signer_name' => $template->signer_name,
            'signer_title' => $template->signer_title,
            'logo_url' => $template->logo_url,
            'signature_url' => $template->signature_url,
            'placeholders' => $placeholders,
            'issued_at' => Carbon::parse($issueDate)->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, string>  $placeholders
     */
    private function replacePlaceholders(string $text, array $placeholders): string
    {
        foreach ($placeholders as $key => $value) {
            $text = str_replace(['{{'.$key.'}}', '{'.$key.'}'], $value, $text);
        }

        return $text;
    }

    private function uniqueNumber(): string
    {
        do {
            $number = 'EBZ-'.now()->format('Y').'-'.Str::upper(Str::random(6));
        } while (WorkshopCertificate::query()->where('certificate_number', $number)->exists());

        return $number;
    }
}
