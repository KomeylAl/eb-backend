<?php

namespace App\Actions\Auth;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginAction
{
    /**
     * @return array{user: User, token: string}
     */
    public function execute(string $phone, string $password, ?UserType $type = null): array
    {
        $query = User::query()->where('phone', $phone);

        if ($type !== null) {
            $query->where('type', $type);
        }

        $user = $query->first();

        if (! $user || ! $user->password || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'phone' => ['اطلاعات ورود اشتباه است.'],
            ]);
        }

        $token = $user->createToken(
            name: 'api',
            abilities: [$user->type->value],
        )->plainTextToken;

        return [
            'user' => $user->loadMissing('doctorProfile'),
            'token' => $token,
        ];
    }
}
