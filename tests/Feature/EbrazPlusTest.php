<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\AssessmentStatus;
use App\Enums\CertificateTemplateKey;
use App\Enums\HomeworkStatus;
use App\Enums\HomeworkType;
use App\Enums\OtpPurpose;
use App\Enums\PaymentStatus;
use App\Enums\ResourceType;
use App\Enums\TreatmentProgramStatus;
use App\Enums\WorkshopType;
use App\Models\Appointment;
use App\Models\Homework;
use App\Models\InitAssessment;
use App\Models\Participant;
use App\Models\Payment;
use App\Models\TreatmentProgram;
use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopCertificate;
use App\Models\WorkshopMaterial;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Mockery\MockInterface;
use Tests\TestCase;

class EbrazPlusTest extends TestCase
{
    use RefreshDatabase;

    private function makeWorkshop(): Workshop
    {
        return Workshop::query()->create([
            'title' => 'CBT Basics',
            'slug' => 'cbt-basics',
            'type' => WorkshopType::General,
            'excerpt' => 'excerpt',
            'content' => 'content',
        ]);
    }

    private function makeParticipant(array $attrs = []): Participant
    {
        return Participant::query()->create(array_merge([
            'name' => 'علی رضایی',
            'phone' => '09121234567',
            'national_code' => '0012345678',
        ], $attrs));
    }

    private function attach(Workshop $workshop, Participant $participant, bool $approved): void
    {
        $workshop->participants()->attach($participant->id, [
            'id' => (string) Str::uuid(),
            'registered_at' => now(),
            'approved' => $approved,
            'joined_at' => $approved ? now() : null,
        ]);
    }

    public function test_approved_participant_can_login_and_access_materials(): void
    {
        $workshop = $this->makeWorkshop();
        $participant = $this->makeParticipant();
        $this->attach($workshop, $participant, true);

        WorkshopMaterial::query()->create([
            'workshop_id' => $workshop->id,
            'title' => 'Slides',
            'type' => ResourceType::Link,
            'link' => 'https://example.com/slides',
        ]);

        $login = $this->postJson('/api/v1/plus/login', [
            'phone' => '09121234567',
            'national_code' => '0012345678',
        ])->assertOk()
            ->assertJsonPath('data.participant.phone', '09121234567')
            ->assertJsonPath('data.user.is_participant', true)
            ->assertJsonPath('data.user.is_client', false);

        $token = $login->json('data.token');
        $this->assertNotEmpty($token);

        $this->withToken($token)
            ->getJson('/api/v1/plus/workshops')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $workshop->id);

        $this->withToken($token)
            ->getJson('/api/v1/plus/workshops/'.$workshop->id.'/materials')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Slides');
    }

    public function test_participant_can_login_with_password_field(): void
    {
        $workshop = $this->makeWorkshop();
        $participant = $this->makeParticipant();
        $this->attach($workshop, $participant, true);

        $this->postJson('/api/v1/plus/login', [
            'phone' => '09121234567',
            'password' => '0012345678',
        ])->assertOk()
            ->assertJsonPath('data.user.phone', '09121234567');
    }

    public function test_unapproved_participant_cannot_login(): void
    {
        $workshop = $this->makeWorkshop();
        $participant = $this->makeParticipant();
        $this->attach($workshop, $participant, false);

        $this->postJson('/api/v1/plus/login', [
            'phone' => '09121234567',
            'national_code' => '0012345678',
        ])->assertStatus(422);
    }

    public function test_wrong_credentials_fail(): void
    {
        $this->postJson('/api/v1/plus/login', [
            'phone' => '09120000000',
            'national_code' => '0000000000',
        ])->assertStatus(422);
    }

    public function test_client_can_login_with_password_and_see_clinical_data(): void
    {
        $client = User::factory()->client()->create([
            'phone' => '09125555555',
            'password' => 'secret-pass',
        ]);
        $doctor = User::factory()->doctor()->create();

        $program = TreatmentProgram::query()->create([
            'client_id' => $client->id,
            'doctor_id' => $doctor->id,
            'title' => 'Anxiety',
            'status' => TreatmentProgramStatus::Active,
            'started_at' => now()->toDateString(),
        ]);

        $appointment = Appointment::query()->create([
            'treatment_program_id' => $program->id,
            'date' => now()->toDateString(),
            'time' => '10:00',
            'amount' => 100000,
            'status' => AppointmentStatus::Pending,
            'session_notes' => 'private therapist notes',
        ]);
        $appointmentId = $appointment->id;

        DB::table('appointment_user')->insert([
            'id' => (string) Str::uuid(),
            'appointment_id' => $appointment->id,
            'doctor_id' => $doctor->id,
            'client_id' => $client->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Payment::query()->create([
            'appointment_id' => $appointment->id,
            'status' => PaymentStatus::Paid,
            'amount' => 100000,
            'paid_amount' => 100000,
        ]);

        $homework = Homework::query()->create([
            'appointment_id' => $appointment->id,
            'type' => HomeworkType::Text,
            'title' => 'Journaling',
            'body' => 'Write daily mood',
            'status' => HomeworkStatus::Assigned,
        ]);
        $homeworkId = $homework->id;

        $assessment = InitAssessment::query()->create([
            'date' => now()->toDateString(),
            'time' => '09:00',
            'status' => AssessmentStatus::Pending,
        ]);
        $assessment->clients()->attach($client->id, [
            'id' => (string) Str::uuid(),
            'doctor_id' => $doctor->id,
        ]);

        $login = $this->postJson('/api/v1/plus/login', [
            'phone' => '09125555555',
            'password' => 'secret-pass',
        ])->assertOk()
            ->assertJsonPath('data.user.is_client', true)
            ->assertJsonPath('data.user.is_participant', false);

        $token = $login->json('data.token');

        $this->withToken($token)
            ->getJson('/api/v1/plus/appointments')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonMissingPath('data.items.0.session_notes');

        $this->withToken($token)
            ->getJson('/api/v1/plus/appointments/'.$appointmentId)
            ->assertOk()
            ->assertJsonPath('data.id', $appointmentId)
            ->assertJsonMissingPath('data.session_notes');

        $this->withToken($token)
            ->getJson('/api/v1/plus/treatment-programs')
            ->assertOk()
            ->assertJsonPath('data.items.0.title', 'Anxiety');

        $this->withToken($token)
            ->getJson('/api/v1/plus/assessments')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1);

        $this->withToken($token)
            ->getJson('/api/v1/plus/homeworks')
            ->assertOk()
            ->assertJsonPath('data.items.0.title', 'Journaling');

        $this->withToken($token)
            ->patchJson('/api/v1/plus/homeworks/'.$homeworkId.'/complete')
            ->assertOk()
            ->assertJsonPath('data.status', HomeworkStatus::Done->value);

        $this->assertDatabaseHas('homeworks', [
            'id' => $homeworkId,
            'status' => HomeworkStatus::Done->value,
            'completed_by' => $client->id,
        ]);

        $this->withToken($token)
            ->getJson('/api/v1/plus/dashboard')
            ->assertOk()
            ->assertJsonPath('data.is_client', true)
            ->assertJsonPath('data.done_homeworks', 1);
    }

    public function test_client_who_is_also_participant_sees_both_sides(): void
    {
        $client = User::factory()->client()->create([
            'name' => 'مریم',
            'phone' => '09126666666',
            'password' => 'clinic-pass',
        ]);
        $participant = $this->makeParticipant([
            'name' => 'مریم',
            'phone' => '09126666666',
            'national_code' => '1122334455',
        ]);
        $workshop = $this->makeWorkshop();
        $this->attach($workshop, $participant, true);

        $login = $this->postJson('/api/v1/plus/login', [
            'phone' => '09126666666',
            'password' => 'clinic-pass',
        ])->assertOk()
            ->assertJsonPath('data.user.is_client', true)
            ->assertJsonPath('data.user.is_participant', true);

        $token = $login->json('data.token');

        $this->withToken($token)
            ->getJson('/api/v1/plus/workshops')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->withToken($token)
            ->getJson('/api/v1/plus/appointments')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 0);
    }

    public function test_client_without_custom_password_can_login_with_national_code(): void
    {
        User::factory()->client()->create([
            'phone' => '09127777777',
            'password' => null,
        ]);
        $participant = $this->makeParticipant([
            'phone' => '09127777777',
            'national_code' => '9988776655',
        ]);
        $workshop = $this->makeWorkshop();
        $this->attach($workshop, $participant, false);

        $this->postJson('/api/v1/plus/login', [
            'phone' => '09127777777',
            'password' => '9988776655',
        ])->assertOk()
            ->assertJsonPath('data.user.is_client', true);
    }

    public function test_changed_password_rejects_national_code(): void
    {
        User::factory()->client()->create([
            'phone' => '09128888888',
            'password' => 'new-secret',
        ]);
        $participant = $this->makeParticipant([
            'phone' => '09128888888',
            'national_code' => '1231231231',
        ]);
        $workshop = $this->makeWorkshop();
        $this->attach($workshop, $participant, true);

        $this->postJson('/api/v1/plus/login', [
            'phone' => '09128888888',
            'password' => '1231231231',
        ])->assertStatus(422);
    }

    public function test_participant_and_client_can_login_with_otp(): void
    {
        $workshop = $this->makeWorkshop();
        $participant = $this->makeParticipant(['phone' => '09121112222']);
        $this->attach($workshop, $participant, true);

        $this->mock(SmsService::class, function (MockInterface $mock) {
            $mock->shouldReceive('send')->once()->andReturn(true);
        });

        $this->postJson('/api/v1/plus/otp/request', [
            'phone' => '09121112222',
        ])->assertOk();

        Cache::put(
            'otp:code:'.OtpPurpose::Login->value.':09121112222',
            Hash::make('123456'),
            now()->addMinutes(5),
        );

        $this->postJson('/api/v1/plus/otp/verify', [
            'phone' => '09121112222',
            'code' => '123456',
        ])->assertOk()
            ->assertJsonPath('data.user.phone', '09121112222');

        User::factory()->client()->create([
            'phone' => '09123334444',
            'password' => null,
        ]);

        $this->mock(SmsService::class, function (MockInterface $mock) {
            $mock->shouldReceive('send')->once()->andReturn(true);
        });

        $this->postJson('/api/v1/plus/otp/request', [
            'phone' => '09123334444',
        ])->assertOk();
    }

    public function test_participant_cannot_access_other_workshop(): void
    {
        $workshopA = $this->makeWorkshop();
        $workshopB = Workshop::query()->create([
            'title' => 'Other',
            'slug' => 'other',
            'type' => WorkshopType::Seminar,
            'excerpt' => 'e',
            'content' => 'c',
        ]);
        $participant = $this->makeParticipant();
        $this->attach($workshopA, $participant, true);

        Sanctum::actingAs($participant, ['participant']);

        $this->getJson('/api/v1/plus/workshops/'.$workshopB->id.'/materials')
            ->assertForbidden();
    }

    public function test_participant_can_see_issued_certificate_payload(): void
    {
        $workshop = $this->makeWorkshop();
        $participant = $this->makeParticipant();
        $this->attach($workshop, $participant, true);

        WorkshopCertificate::query()->create([
            'workshop_id' => $workshop->id,
            'participant_id' => $participant->id,
            'template_key' => CertificateTemplateKey::Classic,
            'certificate_number' => 'EBZ-TEST-1',
            'issued_at' => now(),
            'payload' => [
                'template_key' => 'classic',
                'title' => 'گواهی',
                'body_rendered' => 'علی رضایی',
            ],
        ]);

        Sanctum::actingAs($participant, ['participant']);

        $this->getJson('/api/v1/plus/workshops/'.$workshop->id.'/certificates')
            ->assertOk()
            ->assertJsonPath('data.0.certificate_number', 'EBZ-TEST-1')
            ->assertJsonPath('data.0.payload.body_rendered', 'علی رضایی');
    }

    public function test_participant_only_cannot_access_clinical_routes(): void
    {
        $workshop = $this->makeWorkshop();
        $participant = $this->makeParticipant();
        $this->attach($workshop, $participant, true);
        Sanctum::actingAs($participant, ['plus']);

        $this->getJson('/api/v1/plus/appointments')->assertForbidden();
        $this->getJson('/api/v1/plus/homeworks')->assertForbidden();
        $this->getJson('/api/v1/plus/workshops')->assertOk();
    }
}
