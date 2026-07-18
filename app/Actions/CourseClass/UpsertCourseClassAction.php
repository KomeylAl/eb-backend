<?php

namespace App\Actions\CourseClass;

use App\Models\ClassDate;
use App\Models\CourseClass;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UpsertCourseClassAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, ?CourseClass $courseClass = null): CourseClass
    {
        return DB::transaction(function () use ($data, $courseClass) {
            $payload = collect($data)->only([
                'title',
                'description',
                'start_date',
                'end_date',
                'week_day',
                'time',
            ])->all();

            if ($courseClass) {
                $courseClass->update($payload);
            } else {
                $courseClass = CourseClass::query()->create($payload);
            }

            if (array_key_exists('dates', $data)) {
                $courseClass->dates()->delete();

                foreach ($data['dates'] ?? [] as $date) {
                    ClassDate::query()->create([
                        'class_id' => $courseClass->id,
                        'date' => $date,
                    ]);
                }
            }

            if (array_key_exists('teacher_id', $data) || array_key_exists('student_ids', $data)) {
                $courseClass->users()->detach();

                if (! empty($data['teacher_id'])) {
                    $courseClass->users()->attach($data['teacher_id'], [
                        'id' => (string) Str::uuid(),
                        'role' => 'teacher',
                    ]);
                }

                foreach ($data['student_ids'] ?? [] as $studentId) {
                    $courseClass->users()->attach($studentId, [
                        'id' => (string) Str::uuid(),
                        'role' => 'student',
                    ]);
                }
            }

            return $courseClass->load(['dates', 'users']);
        });
    }
}
