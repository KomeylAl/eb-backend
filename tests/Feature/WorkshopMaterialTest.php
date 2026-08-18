<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\ResourceType;
use App\Enums\WorkshopType;
use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopMaterial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkshopMaterialTest extends TestCase
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

    public function test_admin_can_create_link_and_file_materials(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin(AdminRole::Author)->create();
        Sanctum::actingAs($admin);
        $workshop = $this->makeWorkshop();

        $this->postJson('/api/v1/workshops/'.$workshop->id.'/materials', [
            'title' => 'Slides link',
            'type' => ResourceType::Link->value,
            'link' => 'https://example.com/slides',
            'description' => 'External slides',
        ])->assertCreated()
            ->assertJsonPath('data.type', 'link')
            ->assertJsonPath('data.link', 'https://example.com/slides');

        $file = UploadedFile::fake()->image('lecture.jpg');

        $this->post('/api/v1/workshops/'.$workshop->id.'/materials', [
            'title' => 'Lecture slide',
            'type' => ResourceType::File->value,
            'file' => $file,
        ], [
            'Accept' => 'application/json',
        ])->assertCreated()
            ->assertJsonPath('data.type', 'file')
            ->assertJsonPath('data.has_file', true);

        $this->getJson('/api/v1/workshops/'.$workshop->id.'/materials')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_admin_can_download_and_delete_file_material(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin(AdminRole::Boss)->create();
        Sanctum::actingAs($admin);
        $workshop = $this->makeWorkshop();

        $file = UploadedFile::fake()->image('notes.jpg');
        $materialId = $this->post('/api/v1/workshops/'.$workshop->id.'/materials', [
            'title' => 'Notes',
            'type' => ResourceType::File->value,
            'file' => $file,
        ], [
            'Accept' => 'application/json',
        ])->assertCreated()->json('data.id');

        $this->get('/api/v1/workshops/'.$workshop->id.'/materials/'.$materialId.'/download')
            ->assertOk();

        $this->deleteJson('/api/v1/workshops/'.$workshop->id.'/materials/'.$materialId)
            ->assertNoContent();

        $this->assertDatabaseMissing('workshop_materials', ['id' => $materialId]);
    }

    public function test_guest_cannot_manage_materials(): void
    {
        $workshop = $this->makeWorkshop();

        $this->getJson('/api/v1/workshops/'.$workshop->id.'/materials')
            ->assertUnauthorized();

        $this->postJson('/api/v1/workshops/'.$workshop->id.'/materials', [
            'title' => 'Nope',
            'type' => ResourceType::Link->value,
            'link' => 'https://example.com',
        ])->assertUnauthorized();
    }

    public function test_deleting_workshop_cascades_materials(): void
    {
        $admin = User::factory()->admin(AdminRole::Boss)->create();
        Sanctum::actingAs($admin);
        $workshop = $this->makeWorkshop();

        $material = WorkshopMaterial::query()->create([
            'workshop_id' => $workshop->id,
            'title' => 'Temp',
            'type' => ResourceType::Link,
            'link' => 'https://example.com',
        ]);

        $this->deleteJson('/api/v1/workshops/'.$workshop->id)->assertNoContent();

        $this->assertDatabaseMissing('workshop_materials', ['id' => $material->id]);
    }
}
