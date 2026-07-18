<?php

namespace App\Actions\Auth;

use App\Enums\OtpPurpose;
use App\Enums\UserType;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Validation\ValidationException;

class RequestLoginOtpAction
{
    public function __construct(private OtpService $otp) {}

    public function execute(string $phone, UserType $type): void
    {
        $user = $this->findStaffUser($phone, $type);

        if (! $user) {
            throw ValidationException::withMessages([
                'phone' => ['کاربری با این شماره یافت نشد.'],
            ]);
        }

        $this->otp->send($user->phone, OtpPurpose::Login);
    }

    private function findStaffUser(string $phone, UserType $type): ?User
    {
        $query = User::query()->where('phone', $phone);

        if ($type === UserType::Doctor) {
            $query->actingAsDoctors();
        } elseif ($type === UserType::Admin) {
            $query->where('type', UserType::Admin);
        } else {
            return null;
        }

        return $query->first();
    }
}
