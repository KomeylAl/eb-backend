<?php

namespace App\Http\Resources;

use App\Models\TreatmentProgram;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TreatmentProgram */
class TreatmentProgramResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'doctor_id' => $this->doctor_id,
            'title' => $this->title,
            'status' => $this->status?->value,
            'started_at' => $this->started_at?->toDateString(),
            'ended_at' => $this->ended_at?->toDateString(),
            'client' => ClientResource::make($this->whenLoaded('client')),
            'doctor' => DoctorResource::make($this->whenLoaded('doctor')),
            'medical_record' => MedicalRecordResource::make($this->whenLoaded('medicalRecord')),
            'appointments_count' => $this->whenCounted('appointments'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
