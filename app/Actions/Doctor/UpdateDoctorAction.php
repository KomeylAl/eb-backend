<?php

namespace App\Actions\Doctor;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UpdateDoctorAction
{
    public function execute(User $doctor, array $data, ?UploadedFile $avatar = null): User
    {
        return DB::transaction(function () use ($doctor, $data, $avatar) {
            $userPayload = [
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'birth_date' => $data['birth_date'] ?? null,
            ];

            if (! empty($data['password'])) {
                $userPayload['password'] = $data['password'];
            }

            $doctor->update($userPayload);

            $profile = $doctor->doctorProfile()->firstOrNew(['user_id' => $doctor->id]);

            $profilePayload = [
                'national_code' => $data['national_code'],
                'card_number' => $data['card_number'] ?? null,
                'medical_number' => $data['medical_number'] ?? null,
                'days' => $data['days'] ?? $profile->days,
                'times' => $data['times'] ?? $profile->times,
            ];

            if (array_key_exists('sort_order', $data)) {
                $profilePayload['sort_order'] = $data['sort_order'];
            }

            if ($avatar) {
                if ($profile->avatar) {
                    Storage::disk('public')->delete($profile->avatar);
                }
                $profilePayload['avatar'] = $avatar->store('doctor_avatars', 'public');
            }

            $profile->fill($profilePayload);
            $profile->user_id = $doctor->id;
            $profile->save();

            if (array_key_exists('department_ids', $data)) {
                $this->syncDepartments($doctor, $data['department_ids'] ?? []);
            }

            return $doctor->load(['doctorProfile', 'departments', 'resume', 'doctorResources']);
        });
    }

    private function syncDepartments(User $doctor, array $departmentIds): void
    {
        DB::table('department_doctor')->where('doctor_id', $doctor->id)->delete();

        $now = now();

        foreach ($departmentIds as $departmentId) {
            DB::table('department_doctor')->insert([
                'id' => (string) Str::uuid(),
                'department_id' => $departmentId,
                'doctor_id' => $doctor->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
