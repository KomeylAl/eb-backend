<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\TreatmentProgramStatus;
use App\Models\MedicalRecord;
use App\Models\TreatmentProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MedicalRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upsert_medical_record(): void
    {
        $admin = User::factory()->admin(AdminRole::Boss)->create();
        $client = User::factory()->client()->create();
        $doctor = User::factory()->doctor()->create();
        Sanctum::actingAs($admin);

        $program = TreatmentProgram::query()->create([
            'client_id' => $client->id,
            'doctor_id' => $doctor->id,
            'title' => 'Program',
            'status' => TreatmentProgramStatus::Active,
            'started_at' => now()->toDateString(),
        ]);

        $response = $this->postJson('/api/v1/treatment-programs/'.$program->id.'/medical-record', [
            'record_number' => 'REC-100',
            'admin_id' => $admin->id,
            'companion_name' => 'Ali',
            'companion_phone' => '09121112233',
            'chief_complaints' => 'Anxiety',
            'diagnosis' => 'GAD',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.record_number', 'REC-100')
            ->assertJsonPath('data.chief_complaints', 'Anxiety')
            ->assertJsonPath('data.companion.name', 'Ali');

        $this->assertDatabaseHas('medical_records', [
            'treatment_program_id' => $program->id,
            'record_number' => 'REC-100',
        ]);
    }

    public function test_admin_can_change_doctor_on_medical_record(): void
    {
        $admin = User::factory()->admin(AdminRole::Boss)->create();
        $client = User::factory()->client()->create();
        $doctor = User::factory()->doctor()->create();
        $otherDoctor = User::factory()->doctor()->create();

        $program = TreatmentProgram::query()->create([
            'client_id' => $client->id,
            'doctor_id' => $doctor->id,
            'title' => 'Program',
            'status' => TreatmentProgramStatus::Active,
            'started_at' => now()->toDateString(),
        ]);

        MedicalRecord::query()->create([
            'treatment_program_id' => $program->id,
            'client_id' => $client->id,
            'doctor_id' => $doctor->id,
            'record_number' => 'REC-DOC-CHANGE',
        ]);

        Sanctum::actingAs($admin);

        $this->putJson('/api/v1/treatment-programs/'.$program->id.'/medical-record', [
            'record_number' => 'REC-DOC-CHANGE',
            'doctor_id' => $otherDoctor->id,
            'chief_complaints' => 'Updated',
        ])->assertCreated()
            ->assertJsonPath('data.doctor_id', $otherDoctor->id);

        $this->assertDatabaseHas('treatment_programs', [
            'id' => $program->id,
            'doctor_id' => $otherDoctor->id,
        ]);

        $this->assertDatabaseHas('medical_records', [
            'treatment_program_id' => $program->id,
            'doctor_id' => $otherDoctor->id,
        ]);
    }

    public function test_doctor_can_view_and_update_clinical_fields_for_program(): void
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

        MedicalRecord::query()->create([
            'treatment_program_id' => $program->id,
            'client_id' => $client->id,
            'doctor_id' => $doctor->id,
            'admin_id' => $admin->id,
            'record_number' => 'REC-200',
            'chief_complaints' => 'Old',
            'reference_source' => 'Clinic',
        ]);

        Sanctum::actingAs($doctor);

        $this->getJson('/api/v1/doctor/treatment-programs/'.$program->id.'/medical-record')
            ->assertOk()
            ->assertJsonPath('data.program.id', $program->id)
            ->assertJsonPath('data.record.record_number', 'REC-200');

        $this->putJson('/api/v1/doctor/treatment-programs/'.$program->id.'/medical-record', [
            'chief_complaints' => 'Updated complaint',
            'diagnosis' => 'New diagnosis',
            'record_number' => 'HACKED',
            'companion_name' => 'ShouldIgnore',
            'reference_source' => 'ShouldIgnore',
        ])->assertOk()
            ->assertJsonPath('data.chief_complaints', 'Updated complaint')
            ->assertJsonPath('data.diagnosis', 'New diagnosis')
            ->assertJsonPath('data.record_number', 'REC-200')
            ->assertJsonPath('data.reference_source', 'Clinic');

        $this->assertDatabaseMissing('companions', ['name' => 'ShouldIgnore']);
    }

    public function test_doctor_cannot_access_unrelated_program_record(): void
    {
        $doctor = User::factory()->doctor()->create();
        $otherDoctor = User::factory()->doctor()->create();
        $client = User::factory()->client()->create();

        $program = TreatmentProgram::query()->create([
            'client_id' => $client->id,
            'doctor_id' => $otherDoctor->id,
            'title' => 'Other',
            'status' => TreatmentProgramStatus::Active,
            'started_at' => now()->toDateString(),
        ]);

        MedicalRecord::query()->create([
            'treatment_program_id' => $program->id,
            'client_id' => $client->id,
            'doctor_id' => $otherDoctor->id,
            'record_number' => 'REC-300',
        ]);

        Sanctum::actingAs($doctor);

        $this->getJson('/api/v1/doctor/treatment-programs/'.$program->id.'/medical-record')
            ->assertForbidden();
    }
}
