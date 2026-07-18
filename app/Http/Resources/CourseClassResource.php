<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\CourseClass */
class CourseClassResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $teacher = null;
        $students = [];

        if ($this->relationLoaded('users')) {
            $teacherUser = $this->users->firstWhere('pivot.role', 'teacher');
            $teacher = $teacherUser ? [
                'id' => $teacherUser->id,
                'name' => $teacherUser->name,
                'phone' => $teacherUser->phone,
            ] : null;

            $students = $this->users
                ->where('pivot.role', 'student')
                ->map(fn ($user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                ])
                ->values();
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'week_day' => $this->week_day,
            'time' => $this->time,
            'teacher' => $teacher,
            'students' => $students,
            'dates' => ClassDateResource::collection($this->whenLoaded('dates')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
