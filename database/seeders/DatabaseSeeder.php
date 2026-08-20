<?php

namespace Database\Seeders;

use App\Enums\AdminRole;
use App\Enums\UserType;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['phone' => '09131889355'],
            [
                'name' => 'Dr. Ali Mehrabi',
                'email' => 'ali.mehrabi@gmail.com',
                'password' => '123ebraz90',
                'birth_date' => '1980-01-01',
                'type' => UserType::Admin,
                'admin_role' => AdminRole::Boss,
            ],
        );

        $this->call(HeroSeeder::class);
    }
}
