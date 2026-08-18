<?php

namespace App\Actions\Plus;

use App\Models\Participant;
use App\Models\User;
use App\Support\PlusIdentity;

class IssuePlusSessionAction
{
    /**
     * @return array{identity: PlusIdentity, token: string}
     */
    public function execute(?User $client, ?Participant $participant): array
    {
        $actor = $client ?? $participant;
        abort_unless($actor, 500);

        $abilities = $client ? ['client', 'plus'] : ['participant', 'plus'];

        $token = $actor->createToken(
            name: 'ebraz-plus',
            abilities: $abilities,
        )->plainTextToken;

        return [
            'identity' => new PlusIdentity($actor, $client, $participant),
            'token' => $token,
        ];
    }
}
