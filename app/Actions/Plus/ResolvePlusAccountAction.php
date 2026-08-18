<?php

namespace App\Actions\Plus;

use App\Enums\UserType;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ResolvePlusAccountAction
{
    /**
     * @return array{client: ?User, participant: ?Participant}
     */
    public function execute(string $phone): array
    {
        $phone = trim($phone);

        $client = User::query()
            ->where('phone', $phone)
            ->where('type', UserType::Client)
            ->first();

        $participant = Participant::query()
            ->where('phone', $phone)
            ->first();

        $approvedParticipant = $participant && $participant->approvedWorkshops()->exists()
            ? $participant
            : null;

        if (! $client && ! $approvedParticipant) {
            throw ValidationException::withMessages([
                'phone' => ['کاربری با این شماره یافت نشد.'],
            ]);
        }

        return [
            'client' => $client,
            'participant' => $participant,
        ];
    }
}
