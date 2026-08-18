<?php

namespace App\Services;

use App\Enums\UserType;
use App\Http\Resources\ClientResource;
use App\Http\Resources\PlusParticipantResource;
use App\Http\Resources\PlusProfileResource;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PlusAuthService
{
    public function findClient(string $phone): ?User
    {
        return User::query()
            ->where('phone', $phone)
            ->where('type', UserType::Client)
            ->first();
    }

    public function findParticipant(string $phone): ?Participant
    {
        return Participant::query()->where('phone', $phone)->first();
    }

    public function canAccessPlus(?User $client, ?Participant $participant): bool
    {
        if ($client) {
            return true;
        }

        return $participant !== null && $participant->approvedWorkshops()->exists();
    }

    public function assertCanAccessPlus(?User $client, ?Participant $participant): void
    {
        if ($this->canAccessPlus($client, $participant)) {
            return;
        }

        if ($participant && ! $participant->approvedWorkshops()->exists()) {
            throw ValidationException::withMessages([
                'phone' => ['هنوز در هیچ کارگاهی تأیید نشده‌اید.'],
            ]);
        }

        throw ValidationException::withMessages([
            'phone' => ['کاربری با این شماره یافت نشد.'],
        ]);
    }

    public function credentialsMatch(string $password, ?User $client, ?Participant $participant): bool
    {
        if ($client?->password && Hash::check($password, $client->password)) {
            return true;
        }

        if ($client?->password) {
            return false;
        }

        $nationalCode = $participant?->national_code;

        return is_string($nationalCode)
            && $nationalCode !== ''
            && hash_equals($nationalCode, $password);
    }

    /**
     * @return array{user: array<string, mixed>, participant: mixed, client: mixed, token: string, token_type: string}
     */
    public function issueSession(?User $client, ?Participant $participant): array
    {
        $this->assertCanAccessPlus($client, $participant);

        /** @var Authenticatable $actor */
        $actor = $client ?? $participant;

        $abilities = $client ? ['client', 'plus'] : ['participant', 'plus'];
        $token = $actor->createToken('ebraz-plus', $abilities)->plainTextToken;

        return [
            'user' => PlusProfileResource::makeFrom($client, $participant),
            'participant' => $participant ? PlusParticipantResource::make($participant) : null,
            'client' => $client ? ClientResource::make($client) : null,
            'token' => $token,
            'token_type' => 'Bearer',
        ];
    }
}
