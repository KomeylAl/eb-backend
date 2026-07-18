<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\UserType;
use App\Models\DoctorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DoctorSortOrderTest extends TestCase
{
    use RefreshDatabase;

    private function actingAdmin(): User
    {
        $admin = User::factory()->admin(AdminRole::Boss)->create();
        Sanctum::actingAs($admin);

        return $admin;
    }

    private function createDoctorWithSortOrder(string $name, string $phone, string $nationalCode, int $sortOrder): User
    {
        $doctor = User::factory()->doctor()->create([
            'name' => $name,
            'phone' => $phone,
        ]);

        DoctorProfile::query()->create([
            'user_id' => $doctor->id,
            'national_code' => $nationalCode,
            'sort_order' => $sortOrder,
        ]);

        return $doctor;
    }

    public function test_doctors_are_listed_by_sort_order_ascending_by_default(): void
    {
        $third = $this->createDoctorWithSortOrder('Doctor C', '09121111111', '1111111111', 2);
        $first = $this->createDoctorWithSortOrder('Doctor A', '09122222222', '2222222222', 0);
        $second = $this->createDoctorWithSortOrder('Doctor B', '09123333333', '3333333333', 1);

        $response = $this->getJson('/api/v1/doctors');

        $response->assertOk();
        $ids = collect($response->json('data.items'))->pluck('id')->all();

        $this->assertSame([$first->id, $second->id, $third->id], $ids);
        $this->assertSame(0, $response->json('data.items.0.doctor_profile.sort_order'));
        $this->assertSame(1, $response->json('data.items.1.doctor_profile.sort_order'));
        $this->assertSame(2, $response->json('data.items.2.doctor_profile.sort_order'));
    }

    public function test_admin_can_create_doctor_with_sort_order(): void
    {
        $this->actingAdmin();

        $response = $this->postJson('/api/v1/doctors', [
            'name' => 'Dr Sorted',
            'phone' => '09125555555',
            'email' => 'sorted@example.com',
            'password' => 'password',
            'national_code' => '0011223344',
            'sort_order' => 5,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', UserType::Doctor->value)
            ->assertJsonPath('data.doctor_profile.sort_order', 5);

        $this->assertDatabaseHas('doctor_profiles', [
            'national_code' => '0011223344',
            'sort_order' => 5,
        ]);
    }

    public function test_admin_can_reorder_doctors(): void
    {
        $this->actingAdmin();

        $first = $this->createDoctorWithSortOrder('Doctor A', '09121111111', '1111111111', 0);
        $second = $this->createDoctorWithSortOrder('Doctor B', '09122222222', '2222222222', 1);
        $third = $this->createDoctorWithSortOrder('Doctor C', '09123333333', '3333333333', 2);

        $response = $this->putJson('/api/v1/doctors/reorder', [
            'ordered_ids' => [$third->id, $first->id, $second->id],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.0.id', $third->id)
            ->assertJsonPath('data.0.doctor_profile.sort_order', 0)
            ->assertJsonPath('data.1.id', $first->id)
            ->assertJsonPath('data.1.doctor_profile.sort_order', 1)
            ->assertJsonPath('data.2.id', $second->id)
            ->assertJsonPath('data.2.doctor_profile.sort_order', 2);

        $this->assertDatabaseHas('doctor_profiles', [
            'user_id' => $third->id,
            'sort_order' => 0,
        ]);
        $this->assertDatabaseHas('doctor_profiles', [
            'user_id' => $first->id,
            'sort_order' => 1,
        ]);
        $this->assertDatabaseHas('doctor_profiles', [
            'user_id' => $second->id,
            'sort_order' => 2,
        ]);
    }

    public function test_admin_can_update_doctor_sort_order(): void
    {
        $this->actingAdmin();

        $doctor = $this->createDoctorWithSortOrder('Doctor A', '09121111111', '1111111111', 0);

        $response = $this->putJson("/api/v1/doctors/{$doctor->id}", [
            'name' => 'Doctor A',
            'phone' => '09121111111',
            'national_code' => '1111111111',
            'sort_order' => 10,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.doctor_profile.sort_order', 10);

        $this->assertDatabaseHas('doctor_profiles', [
            'user_id' => $doctor->id,
            'sort_order' => 10,
        ]);
    }
}
