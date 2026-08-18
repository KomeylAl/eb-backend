<?php

namespace App\Actions\Plus;

use App\Support\PlusIdentity;

class ParticipantLoginAction
{
    public function __construct(private LoginParticipantAction $login) {}

    /**
     * @return array{identity: PlusIdentity, token: string}
     */
    public function execute(string $phone, string $nationalCode): array
    {
        return $this->login->execute($phone, $nationalCode);
    }
}
