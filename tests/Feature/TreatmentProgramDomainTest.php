<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\AppointmentStatus;
use App\Enums\HomeworkType;
use App\Enums\PaymentStatus;
use App\Enums\TreatmentProgramStatus;
use App\Models\MedicalRecord;
use App\Models\Room;
use App\Models\TreatmentProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TreatmentProgramDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_program_and_medical_record(): void
    {
        $admin = User::factory()->admin(AdminRole::Boss)->create();
        $client = User::factory()->client()->create();
        $doctor = User::factory()->doctor()->create();
        Sanctum::actingAs($admin);

        $programResponse = $this->postJson('/api/v1/treatment-programs', [
            'client_id' => $client->id,
            'doctor_id' => $doctor->id,
            'title' => 'Anxiety program',
        ])->assertCreated();

        $programId = $programResponse->json('data.id');

        $this->postJson('/api/v1/treatment-programs/'.$programId.'/medical-record', [
            'record_number' => 'REC-TP-1',
            'admin_id' => $admin->id,
            'chief_complaints' => 'Anxiety',
        ])->assertCreated()
            ->assertJsonPath('data.treatment_program_id', $programId)
            ->assertJsonPath('data.client_id', $client->id)
            ->assertJsonPath('data.doctor_id', $doctor->id);
    }

    public function test_appointment_requires_program_and_can_create_program_inline(): void
    {
        $admin = User::factory()->admin(AdminRole::Boss)->create();
        $client = User::factory()->client()->create();
        $doctor = User::factory()->doctor()->create();
        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/appointments', [
            'create_treatment_program' => true,
            'program_title' => 'New course',
            'doctor_id' => $doctor->id,
            'client_id' => $client->id,
            'date' => now()->toDateString(),
            'time' => '10:00',
            'amount' => 100000,
            'status' => AppointmentStatus::Pending->value,
            'payment_status' => PaymentStatus::Unpaid->value,
        ])->assertCreated()
            ->assertJsonPath('data.treatment_program.title', 'New course');

        $this->assertDatabaseHas('treatment_programs', [
            'client_id' => $client->id,
            'doctor_id' => $doctor->id,
            'title' => 'New course',
            'status' => TreatmentProgramStatus::Active->value,
        ]);
    }

    public function test_doctor_can_update_session_notes_and_homework(): void
    {
        $doctor = User::factory()->doctor()->create();
        $client = User::factory()->client()->create();
        $admin = User::factory()->admin(AdminRole::Boss)->create();

        $program = TreatmentProgram::query()->create([
            'client_id' => $client->id,
            'doctor_id' => $doctor->id,
            'title' => 'Program',
            'status' => TreatmentProgramStatus::Active,
            'started_at' => now()->toDateString(),
        ]);

        Sanctum::actingAs($admin);
        $appointmentId = $this->postJson('/api/v1/appointments', [
            'treatment_program_id' => $program->id,
            'doctor_id' => $doctor->id,
            'client_id' => $client->id,
            'date' => now()->toDateString(),
            'time' => '11:00',
            'amount' => 50000,
            'status' => AppointmentStatus::Pending->value,
            'payment_status' => PaymentStatus::Paid->value,
        ])->assertCreated()->json('data.id');

        Sanctum::actingAs($doctor);

        $this->patchJson('/api/v1/doctor/appointments/'.$appointmentId.'/session-notes', [
            'session_notes' => 'Good progress',
        ])->assertOk()
            ->assertJsonPath('data.session_notes', 'Good progress');

        $homeworkId = $this->postJson('/api/v1/doctor/appointments/'.$appointmentId.'/homeworks', [
            'type' => HomeworkType::Text->value,
            'title' => 'Journaling',
            'body' => 'Write daily mood',
        ])->assertCreated()->json('data.id');

        $this->patchJson('/api/v1/doctor/homeworks/'.$homeworkId, [
            'status' => 'done',
        ])->assertOk()
            ->assertJsonPath('data.status', 'done');
    }

    public function test_room_conflict_is_validated(): void
    {
        $admin = User::factory()->admin(AdminRole::Boss)->create();
        $client = User::factory()->client()->create();
        $doctor = User::factory()->doctor()->create();
        $room = Room::query()->create(['name' => 'Room A', 'code' => 'A1', 'is_active' => true]);

        $program = TreatmentProgram::query()->create([
            'client_id' => $client->id,
            'doctor_id' => $doctor->id,
            'title' => 'Program',
            'status' => TreatmentProgramStatus::Active,
            'started_at' => now()->toDateString(),
        ]);

        Sanctum::actingAs($admin);

        $payload = [
            'treatment_program_id' => $program->id,
            'doctor_id' => $doctor->id,
            'client_id' => $client->id,
            'room_id' => $room->id,
            'date' => now()->toDateString(),
            'time' => '12:00',
            'amount' => 50000,
            'status' => AppointmentStatus::Pending->value,
            'payment_status' => PaymentStatus::Paid->value,
        ];

        $this->postJson('/api/v1/appointments', $payload)->assertCreated();
        $this->postJson('/api/v1/appointments', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['room_id']);
    }

    public function test_doctor_program_medical_record_access(): void
    {
        $doctor = User::factory()->doctor()->create();
        $other = User::factory()->doctor()->create();
        $client = User::factory()->client()->create();

        $program = TreatmentProgram::query()->create([
            'client_id' => $client->id,
            'doctor_id' => $doctor->id,
            'title' => 'Mine',
            'status' => TreatmentProgramStatus::Active,
            'started_at' => now()->toDateString(),
        ]);

        MedicalRecord::query()->create([
            'treatment_program_id' => $program->id,
            'client_id' => $client->id,
            'doctor_id' => $doctor->id,
            'record_number' => 'REC-DOC-1',
        ]);

        Sanctum::actingAs($doctor);
        $this->getJson('/api/v1/doctor/treatment-programs/'.$program->id.'/medical-record')
            ->assertOk()
            ->assertJsonPath('data.record.record_number', 'REC-DOC-1');

        Sanctum::actingAs($other);
        $this->getJson('/api/v1/doctor/treatment-programs/'.$program->id.'/medical-record')
            ->assertForbidden();
    }
}
