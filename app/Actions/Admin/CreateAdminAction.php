<?php

namespace App\Actions\Admin;

use App\Enums\AdminRole;
use App\Enums\UserType;
use App\Models\User;

class CreateAdminAction
{
    public function execute(array $data): User
    {
        return User::query()->create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'password' => $data['password'],
            'birth_date' => $data['birth_date'] ?? null,
            'type' => UserType::Admin,
            'admin_role' => AdminRole::from($data['admin_role']),
        ]);
    }
}
