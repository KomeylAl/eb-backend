<?php

namespace App\Actions\Client;

use App\Enums\UserType;
use App\Models\User;

class CreateClientAction
{
    public function execute(array $data): User
    {
        $payload = [
            'name' => $data['name'],
            'phone' => $data['phone'],
            'birth_date' => $data['birth_date'] ?? null,
            'address' => $data['address'] ?? null,
            'type' => UserType::Client,
        ];

        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        return User::query()->create($payload);
    }
}
