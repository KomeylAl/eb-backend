<?php

namespace App\Actions\Client;

use App\Models\User;

class UpdateClientAction
{
    public function execute(User $client, array $data): User
    {
        $payload = [
            'name' => $data['name'],
            'phone' => $data['phone'],
            'birth_date' => $data['birth_date'] ?? null,
            'address' => $data['address'] ?? null,
        ];

        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        $client->update($payload);

        return $client->refresh();
    }
}
