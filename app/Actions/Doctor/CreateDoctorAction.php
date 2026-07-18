<?php

namespace App\Actions\Doctor;

use App\Enums\UserType;
use App\Models\DoctorProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateDoctorAction
{
    public function execute(array $data, ?UploadedFile $avatar = null): User
    {
        return DB::transaction(function () use ($data, $avatar) {
            $userPayload = [
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'birth_date' => $data['birth_date'] ?? null,
                'type' => UserType::Doctor,
            ];

            if (! empty($data['password'])) {
                $userPayload['password'] = $data['password'];
            }

            $doctor = User::query()->create($userPayload);

            $avatarPath = null;
            if ($avatar) {
                $avatarPath = $avatar->store('doctor_avatars', 'public');
            }

            $maxSortOrder = DoctorProfile::query()->max('sort_order');
            $sortOrder = array_key_exists('sort_order', $data)
                ? (int) $data['sort_order']
                : ($maxSortOrder === null ? 0 : ((int) $maxSortOrder + 1));

            $doctor->doctorProfile()->create([
                'national_code' => $data['national_code'],
                'card_number' => $data['card_number'] ?? null,
                'medical_number' => $data['medical_number'] ?? null,
                'avatar' => $avatarPath,
                'days' => $data['days'] ?? null,
                'times' => $data['times'] ?? null,
                'sort_order' => $sortOrder,
            ]);

            $this->syncDepartments($doctor, $data['department_ids'] ?? []);

            return $doctor->load(['doctorProfile', 'departments']);
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
