<?php

namespace App\Actions\Auth;

use App\Enums\OtpPurpose;
use App\Enums\UserType;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Validation\ValidationException;

class RequestPasswordChangeOtpAction
{
    public function __construct(private OtpService $otp) {}

    public function execute(User $user): void
    {
        if (! in_array($user->type, [UserType::Admin, UserType::Doctor, UserType::Client], true) && ! $user->isActingAsDoctor()) {
            throw ValidationException::withMessages([
                'phone' => ['تغییر رمز فقط برای ادمین و درمانگر مجاز است.'],
            ]);
        }

        $this->otp->send($user->phone, OtpPurpose::PasswordChange);
    }
}
