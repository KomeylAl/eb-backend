<?php

namespace Database\Factories;

use App\Enums\AdminRole;
use App\Enums\UserType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => fake()->unique()->numerify('09#########'),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'birth_date' => fake()->date(),
            'address' => null,
            'type' => UserType::Admin,
            'admin_role' => AdminRole::Boss,
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(?AdminRole $role = AdminRole::Boss): static
    {
        return $this->state(fn () => [
            'type' => UserType::Admin,
            'admin_role' => $role,
        ]);
    }

    public function doctor(): static
    {
        return $this->state(fn () => [
            'type' => UserType::Doctor,
            'admin_role' => null,
            'email' => fake()->unique()->safeEmail(),
        ]);
    }

    public function client(): static
    {
        return $this->state(fn () => [
            'type' => UserType::Client,
            'admin_role' => null,
            'password' => null,
            'email' => null,
            'address' => fake()->address(),
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn () => [
            'email_verified_at' => null,
        ]);
    }
}
