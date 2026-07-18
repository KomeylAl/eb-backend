<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\ResourceType;
use App\Enums\UserType;
use App\Models\DoctorResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DoctorResourceTest extends TestCase
{
    use RefreshDatabase;

    private function actingDoctor(): User
    {
        $doctor = User::factory()->doctor()->create([
            'password' => 'password',
        ]);
        Sanctum::actingAs($doctor);

        return $doctor;
    }

    private function actingAdmin(): User
    {
        $admin = User::factory()->admin(AdminRole::Boss)->create();
        Sanctum::actingAs($admin);

        return $admin;
    }

    public function test_doctor_can_create_link_resource(): void
    {
        $doctor = $this->actingDoctor();

        $response = $this->post('/api/v1/doctor/resources', [
            'title' => 'مقاله مفید',
            'type' => ResourceType::Link->value,
            'description' => 'توضیح کوتاه',
            'link' => 'https://example.com/article',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', ResourceType::Link->value)
            ->assertJsonPath('data.link', 'https://example.com/article')
            ->assertJsonPath('data.file_path', null);

        $this->assertDatabaseHas('doctor_resources', [
            'doctor_id' => $doctor->id,
            'title' => 'مقاله مفید',
            'type' => ResourceType::Link->value,
        ]);
    }

    public function test_doctor_can_create_file_resource(): void
    {
        Storage::fake('public');
        $doctor = $this->actingDoctor();
        $file = UploadedFile::fake()->create('guide.pdf', 100, 'application/pdf');

        $response = $this->post('/api/v1/doctor/resources', [
            'title' => 'راهنما',
            'type' => ResourceType::File->value,
            'file' => $file,
        ], ['Accept' => 'application/json']);

        $response->assertCreated()
            ->assertJsonPath('data.type', ResourceType::File->value)
            ->assertJsonPath('data.link', null);

        $filePath = $response->json('data.file_path');
        $this->assertNotNull($filePath);
        Storage::disk('public')->assertExists($filePath);

        $this->assertDatabaseHas('doctor_resources', [
            'doctor_id' => $doctor->id,
            'title' => 'راهنما',
            'type' => ResourceType::File->value,
        ]);
    }

    public function test_doctor_can_update_and_delete_own_resource(): void
    {
        $doctor = $this->actingDoctor();

        $resource = DoctorResource::query()->create([
            'doctor_id' => $doctor->id,
            'title' => 'قدیمی',
            'type' => ResourceType::Link,
            'link' => 'https://example.com/old',
        ]);

        $this->putJson("/api/v1/doctor/resources/{$resource->id}", [
            'title' => 'جدید',
            'link' => 'https://example.com/new',
        ])->assertOk()
            ->assertJsonPath('data.title', 'جدید')
            ->assertJsonPath('data.link', 'https://example.com/new');

        $this->deleteJson("/api/v1/doctor/resources/{$resource->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('doctor_resources', [
            'id' => $resource->id,
        ]);
    }

    public function test_doctor_cannot_manage_another_doctors_resource(): void
    {
        $owner = User::factory()->doctor()->create();
        $resource = DoctorResource::query()->create([
            'doctor_id' => $owner->id,
            'title' => 'مال دیگری',
            'type' => ResourceType::Link,
            'link' => 'https://example.com',
        ]);

        $this->actingDoctor();

        $this->putJson("/api/v1/doctor/resources/{$resource->id}", [
            'title' => 'هک',
        ])->assertNotFound();

        $this->deleteJson("/api/v1/doctor/resources/{$resource->id}")
            ->assertNotFound();
    }

    public function test_public_doctor_detail_includes_resources(): void
    {
        $doctor = User::factory()->doctor()->create([
            'type' => UserType::Doctor,
        ]);

        DoctorResource::query()->create([
            'doctor_id' => $doctor->id,
            'title' => 'منبع عمومی',
            'type' => ResourceType::Link,
            'description' => 'برای سایت',
            'link' => 'https://example.com/public',
        ]);

        $this->getJson("/api/v1/doctors/{$doctor->id}")
            ->assertOk()
            ->assertJsonPath('data.doctor_resources.0.title', 'منبع عمومی')
            ->assertJsonPath('data.doctor_resources.0.type', ResourceType::Link->value)
            ->assertJsonPath('data.doctor_resources.0.link', 'https://example.com/public');
    }

    public function test_admin_can_manage_doctor_resources(): void
    {
        Storage::fake('public');
        $this->actingAdmin();
        $doctor = User::factory()->doctor()->create();

        $create = $this->post("/api/v1/doctors/{$doctor->id}/resources", [
            'title' => 'ادمین ساخت',
            'type' => ResourceType::File->value,
            'file' => UploadedFile::fake()->create('notes.pdf', 50, 'application/pdf'),
        ], ['Accept' => 'application/json']);

        $create->assertCreated();
        $resourceId = $create->json('data.id');

        $this->putJson("/api/v1/doctors/{$doctor->id}/resources/{$resourceId}", [
            'title' => 'ادمین ویرایش',
        ])->assertOk()
            ->assertJsonPath('data.title', 'ادمین ویرایش');

        $this->deleteJson("/api/v1/doctors/{$doctor->id}/resources/{$resourceId}")
            ->assertNoContent();
    }

    public function test_link_resource_rejects_file_upload(): void
    {
        Storage::fake('public');
        $this->actingDoctor();

        $this->post('/api/v1/doctor/resources', [
            'title' => 'نامعتبر',
            'type' => ResourceType::Link->value,
            'link' => 'https://example.com',
            'file' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'),
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    }
}
