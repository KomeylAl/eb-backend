<?php

namespace App\Actions\Auth;

use App\Enums\OtpPurpose;
use App\Enums\UserType;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Validation\ValidationException;

class VerifyLoginOtpAction
{
    public function __construct(private OtpService $otp) {}

    /**
     * @return array{user: User, token: string}
     */
    public function execute(string $phone, UserType $type, string $code): array
    {
        $user = $this->findStaffUser($phone, $type);

        if (! $user) {
            throw ValidationException::withMessages([
                'phone' => ['کاربری با این شماره یافت نشد.'],
            ]);
        }

        $this->otp->verify($user->phone, OtpPurpose::Login, $code);

        $token = $user->createToken(
            name: 'api',
            abilities: [$user->type->value],
        )->plainTextToken;

        return [
            'user' => $user->loadMissing('doctorProfile'),
            'token' => $token,
        ];
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
