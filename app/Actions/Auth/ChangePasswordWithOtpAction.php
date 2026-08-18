<?php

namespace App\Actions\Auth;

use App\Enums\OtpPurpose;
use App\Enums\UserType;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ChangePasswordWithOtpAction
{
    public function __construct(private OtpService $otp) {}

    public function execute(User $user, string $code, string $password): User
    {
        if (! in_array($user->type, [UserType::Admin, UserType::Doctor, UserType::Client], true) && ! $user->isActingAsDoctor()) {
            throw ValidationException::withMessages([
                'phone' => ['تغییر رمز فقط برای ادمین و درمانگر مجاز است.'],
            ]);
        }

        $this->otp->verify($user->phone, OtpPurpose::PasswordChange, $code);

        if ($user->password && Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['رمز عبور جدید نباید با رمز فعلی یکسان باشد.'],
            ]);
        }

        $user->update([
            'password' => $password,
        ]);

        return $user->fresh();
    }
}
