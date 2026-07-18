<?php

namespace App\Actions\Admin;

use App\Enums\AdminRole;
use App\Models\User;

class UpdateAdminAction
{
    public function execute(User $admin, array $data): User
    {
        $payload = [
            'name' => $data['name'],
            'phone' => $data['phone'],
            'birth_date' => $data['birth_date'] ?? null,
            'admin_role' => AdminRole::from($data['admin_role']),
        ];

        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        $admin->update($payload);

        return $admin->refresh();
    }
}
