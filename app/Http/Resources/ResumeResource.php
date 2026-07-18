<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Resume */
class ResumeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'doctor_id' => $this->doctor_id,
            'title' => $this->title,
            'bio' => $this->bio,
            'specialization' => $this->specialization,
            'educations' => $this->educations,
            'experiences' => $this->experiences,
            'skills' => $this->skills,
            'certifications' => $this->certifications,
            'social_links' => $this->social_links,
            'content' => $this->content,
            'file_path' => $this->file_path,
            'file_url' => $this->file_url,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
