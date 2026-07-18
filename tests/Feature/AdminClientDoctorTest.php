<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\UserType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminClientDoctorTest extends TestCase
{
    use RefreshDatabase;

    private function actingAdmin(): User
    {
        $admin = User::factory()->admin(AdminRole::Boss)->create();
        Sanctum::actingAs($admin);

        return $admin;
    }

    public function test_admin_can_create_client(): void
    {
        $this->actingAdmin();

        $response = $this->postJson('/api/v1/clients', [
            'name' => 'Client One',
            'phone' => '09124444444',
            'birth_date' => '2000-01-01',
            'address' => 'Tehran',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', UserType::Client->value);

        $this->assertDatabaseHas('users', [
            'phone' => '09124444444',
            'type' => UserType::Client->value,
        ]);
    }

    public function test_admin_can_create_doctor_with_profile(): void
    {
        $this->actingAdmin();

        $response = $this->postJson('/api/v1/doctors', [
            'name' => 'Dr Test',
            'phone' => '09125555555',
            'email' => 'doctor@example.com',
            'password' => 'password',
            'birth_date' => '1985-05-05',
            'national_code' => '0011223344',
            'medical_number' => 'MED-1',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', UserType::Doctor->value);

        $this->assertDatabaseHas('doctor_profiles', [
            'national_code' => '0011223344',
        ]);
    }

    public function test_non_admin_cannot_manage_clients(): void
    {
        $doctor = User::factory()->doctor()->create([
            'password' => 'password',
        ]);
        Sanctum::actingAs($doctor);

        $this->getJson('/api/v1/clients')->assertForbidden();
    }
}
