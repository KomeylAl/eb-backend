<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\CertificateTemplateKey;
use App\Enums\WorkshopType;
use App\Models\Participant;
use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopCertificate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkshopCertificateTest extends TestCase
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

    private function attachApproved(Workshop $workshop, Participant $participant): void
    {
        $workshop->participants()->attach($participant->id, [
            'id' => (string) Str::uuid(),
            'registered_at' => now(),
            'approved' => true,
            'joined_at' => now(),
        ]);
    }

    public function test_admin_can_save_template_and_issue_certificate(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin(AdminRole::Author)->create();
        Sanctum::actingAs($admin);
        $workshop = $this->makeWorkshop();

        $participant = Participant::query()->create([
            'name' => 'علی رضایی',
            'english_name' => 'Ali Rezaei',
            'phone' => '09120000000',
            'national_code' => '0012345678',
        ]);
        $this->attachApproved($workshop, $participant);

        $this->post('/api/v1/workshops/'.$workshop->id.'/certificate-template', [
            'template_key' => CertificateTemplateKey::Classic->value,
            'clinic_name' => 'کلینیک ابراز',
            'title' => 'گواهی پایان دوره',
            'body_text' => 'گواهی می‌شود که {{participant_name}} در {{workshop_title}} شرکت کرد.',
            'signer_name' => 'مدیر آموزش',
            'logo' => UploadedFile::fake()->image('logo.png'),
        ], [
            'Accept' => 'application/json',
        ])->assertOk()
            ->assertJsonPath('data.template_key', 'classic')
            ->assertJsonPath('data.clinic_name', 'کلینیک ابراز');

        $this->assertNotNull($workshop->fresh()->certificateTemplate?->logo_path);

        $response = $this->postJson('/api/v1/workshops/'.$workshop->id.'/certificates', [
            'participant_ids' => [$participant->id],
        ])->assertOk();

        $response->assertJsonPath('data.0.participant_id', $participant->id);
        $this->assertDatabaseHas('workshop_certificates', [
            'workshop_id' => $workshop->id,
            'participant_id' => $participant->id,
        ]);

        $payload = $response->json('data.0.payload');
        $this->assertStringContainsString('علی رضایی', (string) ($payload['body_rendered'] ?? ''));
        $this->assertSame('generated', $response->json('data.0.source'));
        $this->assertFalse((bool) $response->json('data.0.has_file'));
    }

    public function test_admin_can_upload_certificate_file_for_participant(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin(AdminRole::Author)->create();
        Sanctum::actingAs($admin);
        $workshop = $this->makeWorkshop();

        $participant = Participant::query()->create([
            'name' => 'مینا',
            'phone' => '09125556677',
            'national_code' => '0099887766',
        ]);
        $this->attachApproved($workshop, $participant);

        $file = UploadedFile::fake()->image('cert.jpg');

        $id = $this->post('/api/v1/workshops/'.$workshop->id.'/certificates/upload', [
            'participant_id' => $participant->id,
            'file' => $file,
        ], [
            'Accept' => 'application/json',
        ])->assertCreated()
            ->assertJsonPath('data.source', 'uploaded')
            ->assertJsonPath('data.has_file', true)
            ->json('data.id');

        $this->get('/api/v1/workshops/'.$workshop->id.'/certificates/'.$id.'/download')
            ->assertOk();

        Sanctum::actingAs($participant, ['participant']);
        $this->get('/api/v1/plus/workshops/'.$workshop->id.'/certificates/'.$id.'/download')
            ->assertOk();
    }

    public function test_cannot_issue_for_unapproved_participant(): void
    {
        $admin = User::factory()->admin(AdminRole::Boss)->create();
        Sanctum::actingAs($admin);
        $workshop = $this->makeWorkshop();

        $this->postJson('/api/v1/workshops/'.$workshop->id.'/certificate-template', [
            'template_key' => CertificateTemplateKey::Minimal->value,
            'title' => 'گواهی',
            'body_text' => 'متن',
        ])->assertOk();

        $participant = Participant::query()->create([
            'name' => 'Pending User',
            'phone' => '09121111111',
            'national_code' => '0011111111',
        ]);
        $workshop->participants()->attach($participant->id, [
            'id' => (string) Str::uuid(),
            'registered_at' => now(),
            'approved' => false,
        ]);

        $this->postJson('/api/v1/workshops/'.$workshop->id.'/certificates', [
            'participant_ids' => [$participant->id],
        ])->assertStatus(422);
    }

    public function test_guest_cannot_manage_certificates(): void
    {
        $workshop = $this->makeWorkshop();

        $this->getJson('/api/v1/workshops/'.$workshop->id.'/certificate-template')
            ->assertUnauthorized();

        $this->postJson('/api/v1/workshops/'.$workshop->id.'/certificates', [
            'participant_ids' => ['00000000-0000-0000-0000-000000000001'],
        ])->assertUnauthorized();
    }

    public function test_deleting_certificate_removes_record_only(): void
    {
        $admin = User::factory()->admin(AdminRole::Boss)->create();
        Sanctum::actingAs($admin);
        $workshop = $this->makeWorkshop();
        $participant = Participant::query()->create([
            'name' => 'Sara',
            'phone' => '09123333333',
            'national_code' => '0033333333',
        ]);
        $this->attachApproved($workshop, $participant);

        $this->postJson('/api/v1/workshops/'.$workshop->id.'/certificate-template', [
            'template_key' => CertificateTemplateKey::Formal->value,
            'body_text' => '{{participant_name}}',
        ])->assertOk();

        $id = $this->postJson('/api/v1/workshops/'.$workshop->id.'/certificates', [
            'participant_ids' => [$participant->id],
        ])->assertOk()->json('data.0.id');

        $this->deleteJson('/api/v1/workshops/'.$workshop->id.'/certificates/'.$id)
            ->assertNoContent();

        $this->assertDatabaseMissing('workshop_certificates', ['id' => $id]);
        $this->assertSame(0, WorkshopCertificate::query()->count());
    }
}
