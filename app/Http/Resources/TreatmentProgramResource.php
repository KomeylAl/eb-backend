<?php

namespace App\Http\Resources;

use App\Enums\AppointmentStatus;
use App\Enums\HomeworkStatus;
use App\Models\TreatmentProgram;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TreatmentProgram */
class TreatmentProgramResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $progress = null;
        if ($this->relationLoaded('appointments')) {
            $appointments = $this->appointments;
            $homeworks = $appointments->flatMap(
                fn ($appointment) => $appointment->relationLoaded('homeworks')
                    ? $appointment->homeworks
                    : collect()
            );

            $sessionsTotal = $appointments->count();
            $sessionsDone = $appointments
                ->where('status', AppointmentStatus::Done)
                ->count();
            $homeworksTotal = $homeworks->count();
            $homeworksDone = $homeworks
                ->where('status', HomeworkStatus::Done)
                ->count();

            $progress = [
                'sessions_total' => $sessionsTotal,
                'sessions_done' => $sessionsDone,
                'sessions_pending' => $appointments
                    ->where('status', AppointmentStatus::Pending)
                    ->count(),
                'homeworks_total' => $homeworksTotal,
                'homeworks_done' => $homeworksDone,
                'homeworks_assigned' => $homeworks
                    ->where('status', HomeworkStatus::Assigned)
                    ->count(),
                'homeworks_cancelled' => $homeworks
                    ->where('status', HomeworkStatus::Cancelled)
                    ->count(),
                'sessions_completion_rate' => $sessionsTotal > 0
                    ? round(($sessionsDone / $sessionsTotal) * 100)
                    : 0,
                'homeworks_completion_rate' => $homeworksTotal > 0
                    ? round(($homeworksDone / $homeworksTotal) * 100)
                    : 0,
            ];
        }

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
            'appointments' => AppointmentResource::collection($this->whenLoaded('appointments')),
            'appointments_count' => $this->whenCounted('appointments'),
            'progress' => $progress,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
