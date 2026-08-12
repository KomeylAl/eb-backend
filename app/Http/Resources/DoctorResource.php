<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
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
            'comments' => CommentResource::collection($this->whenLoaded('receivedComments')),
            'comments_count' => $this->when(isset($this->comments_count), $this->comments_count),
            'rating_avg' => $this->when(isset($this->rating_avg), $this->rating_avg !== null ? round((float) $this->rating_avg, 2) : null),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
