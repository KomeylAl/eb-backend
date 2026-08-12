<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\MedicalRecord */
class MedicalRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'treatment_program_id' => $this->treatment_program_id,
            'client_id' => $this->client_id,
            'companion_id' => $this->companion_id,
            'doctor_id' => $this->doctor_id,
            'supervisor_id' => $this->supervisor_id,
            'admin_id' => $this->admin_id,
            'record_number' => $this->record_number,
            'reference_source' => $this->reference_source,
            'admission_date' => $this->admission_date?->toDateString(),
            'visit_date' => $this->visit_date?->toDateString(),
            'chief_complaints' => $this->chief_complaints,
            'present_illness' => $this->present_illness,
            'past_history' => $this->past_history,
            'family_history' => $this->family_history,
            'personal_history' => $this->personal_history,
            'mse' => $this->mse,
            'diagnosis' => $this->diagnosis,
            'treatment_program' => TreatmentProgramResource::make($this->whenLoaded('treatmentProgram')),
            'client' => ClientResource::make($this->whenLoaded('client')),
            'companion' => CompanionResource::make($this->whenLoaded('companion')),
            'doctor' => DoctorResource::make($this->whenLoaded('doctor')),
            'supervisor' => DoctorResource::make($this->whenLoaded('supervisor')),
            'admin' => AdminResource::make($this->whenLoaded('admin')),
            'images' => RecordImageResource::collection($this->whenLoaded('images')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
