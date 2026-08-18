<?php

namespace App\Actions\Plus;

use App\Enums\UserType;
use App\Models\Participant;
use App\Models\User;
use App\Support\PlusIdentity;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginParticipantAction
{
    public function __construct(private IssuePlusSessionAction $session) {}

    /**
     * @return array{identity: PlusIdentity, token: string}
     */
    public function execute(string $phone, string $password): array
    {
        $phone = trim($phone);
        $password = trim($password);

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
                'phone' => ['اطلاعات ورود اشتباه است.'],
            ]);
        }

        if (! $this->passwordMatches($password, $client, $participant, (bool) $approvedParticipant)) {
            throw ValidationException::withMessages([
                'phone' => ['اطلاعات ورود اشتباه است.'],
            ]);
        }

        return $this->session->execute($client, $participant);
    }

    private function passwordMatches(
        string $password,
        ?User $client,
        ?Participant $participant,
        bool $hasApprovedWorkshop,
    ): bool {
        if ($client?->password && Hash::check($password, $client->password)) {
            return true;
        }

        // If the client has set a custom password, national ID is no longer accepted.
        if ($client?->password) {
            return false;
        }

        $nationalCode = $participant?->national_code;
        if (! is_string($nationalCode) || $nationalCode === '') {
            return false;
        }

        if (! hash_equals($nationalCode, $password)) {
            return false;
        }

        // Clients without a custom password can use their participant national ID.
        // Workshop-only users need at least one approved event.
        return $client !== null || $hasApprovedWorkshop;
    }
}
