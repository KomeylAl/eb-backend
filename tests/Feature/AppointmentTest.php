<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\AppointmentStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserType;
use App\Models\DoctorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AppointmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_appointment_with_payment(): void
    {
        $admin = User::factory()->admin(AdminRole::Boss)->create();
        Sanctum::actingAs($admin);

        $doctor = User::factory()->doctor()->create(['password' => 'password']);
        DoctorProfile::query()->create([
            'user_id' => $doctor->id,
            'national_code' => '1234567890',
        ]);

        $client = User::factory()->client()->create();

        $program = \App\Models\TreatmentProgram::query()->create([
            'client_id' => $client->id,
            'doctor_id' => $doctor->id,
            'title' => 'Program',
            'status' => \App\Enums\TreatmentProgramStatus::Active,
            'started_at' => now()->toDateString(),
        ]);

        $response = $this->postJson('/api/v1/appointments', [
            'treatment_program_id' => $program->id,
            'doctor_id' => $doctor->id,
            'client_id' => $client->id,
            'date' => now()->toDateString(),
            'time' => '10:00',
            'amount' => 500000,
            'status' => AppointmentStatus::Pending->value,
            'payment_status' => PaymentStatus::Paid->value,
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('appointments', [
            'amount' => 500000,
            'status' => AppointmentStatus::Pending->value,
        ]);

        $this->assertDatabaseHas('payments', [
            'amount' => 500000,
            'paid_amount' => 500000,
            'status' => PaymentStatus::Paid->value,
        ]);

        $this->assertDatabaseHas('appointment_user', [
            'doctor_id' => $doctor->id,
            'client_id' => $client->id,
        ]);

        $this->assertSame(UserType::Doctor, $doctor->fresh()->type);
    }
}
