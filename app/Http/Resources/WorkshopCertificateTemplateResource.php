<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\WorkshopCertificateTemplate */
class WorkshopCertificateTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workshop_id' => $this->workshop_id,
            'template_key' => $this->template_key?->value,
            'clinic_name' => $this->clinic_name,
            'title' => $this->title,
            'body_text' => $this->body_text,
            'footer_text' => $this->footer_text,
            'signer_name' => $this->signer_name,
            'signer_title' => $this->signer_title,
            'logo_url' => $this->logo_url,
            'signature_url' => $this->signature_url,
            'placeholders' => [
                'participant_name',
                'english_name',
                'national_code',
                'phone',
                'workshop_title',
                'workshop_type',
                'start_date',
                'end_date',
                'issue_date',
                'certificate_number',
                'clinic_name',
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
