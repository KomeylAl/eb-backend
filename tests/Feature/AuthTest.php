<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\UserType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_and_receive_token(): void
    {
        User::factory()->admin(AdminRole::Boss)->create([
            'phone' => '09121111111',
            'password' => 'password',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'phone' => '09121111111',
            'password' => 'password',
            'type' => 'admin',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Logged in successfully.')
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'token_type',
                    'user' => ['id', 'phone', 'type'],
                ],
            ]);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_authenticated_user_can_view_me_and_logout(): void
    {
        $user = User::factory()->admin()->create([
            'phone' => '09123333333',
            'password' => 'password',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.phone', '09123333333')
            ->assertJsonPath('data.type', UserType::Admin->value);

        $this->postJson('/api/v1/auth/logout')->assertOk();
    }
}
