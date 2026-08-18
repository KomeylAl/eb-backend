<?php

namespace App\Http\Resources;

use App\Models\Participant;
use App\Models\User;

class PlusProfileResource
{
    /**
     * @return array<string, mixed>
     */
    public static function makeFrom(?User $client, ?Participant $participant): array
    {
        $name = $client?->name ?: $participant?->name ?: '';
        $phone = $client?->phone ?: $participant?->phone ?: '';

        return [
            'id' => $client?->id ?: $participant?->id,
            'name' => $name,
            'phone' => $phone,
            'birth_date' => $client?->birth_date?->toDateString(),
            'address' => $client?->address,
            'english_name' => $participant?->english_name,
            'gender' => $participant?->gender?->value,
            'is_client' => $client !== null,
            'is_participant' => $participant !== null,
            'has_password' => filled($client?->password),
            'type' => 'plus',
        ];
    }
}
