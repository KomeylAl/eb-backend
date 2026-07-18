<?php

namespace App\Actions\Doctor;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class SetDoctorPasswordAction
{
    public function execute(User $doctor, string $password): User
    {
        if ($doctor->password && Hash::check($password, $doctor->password)) {
            throw ValidationException::withMessages([
                'password' => ['رمز عبور جدید نمیتواند مشابه رمز عبور قبلی باشد.'],
            ]);
        }

        $doctor->update([
            'password' => $password,
        ]);

        return $doctor->refresh();
    }
}
