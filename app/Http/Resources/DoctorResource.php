<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class DoctorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'birth_date' => $this->birth_date?->toDateString(),
            'type' => $this->type?->value,
            'doctor_profile' => DoctorProfileResource::make($this->whenLoaded('doctorProfile')),
            'departments' => DepartmentResource::collection($this->whenLoaded('departments')),
            'resume' => ResumeResource::make($this->whenLoaded('resume')),
            'doctor_resources' => DoctorResourceItemResource::collection($this->whenLoaded('doctorResources')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
