<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\PostStatus;
use App\Enums\TreatmentProgramStatus;
use App\Models\Media;
use App\Models\TreatmentProgram;
use App\Models\User;
use App\Services\FileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MediaLibraryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');
    }

    public function test_author_can_upload_and_list_library_media(): void
    {
        $author = $this->actingAuthor();
        $file = UploadedFile::fake()->image('cover.jpg', 200, 200);

        $response = $this->post('/api/v1/media', [
            'file' => $file,
            'collection' => 'posts',
            'name' => 'cover-hero',
        ], ['Accept' => 'application/json']);

        $response->assertCreated()
            ->assertJsonPath('data.collection', 'posts')
            ->assertJsonPath('data.name', 'cover-hero');

        $path = $response->json('data.path');
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);

        $this->getJson('/api/v1/media')->assertOk()
            ->assertJsonPath('data.meta.total', 1);

        $this->getJson('/api/v1/media/collections')->assertOk()
            ->assertJsonFragment(['key' => 'posts']);
    }

    public function test_post_can_use_existing_media_id_and_file_survives_post_delete(): void
    {
        $author = $this->actingAuthor();
        $file = UploadedFile::fake()->image('thumb.png');

        $media = $this->post('/api/v1/media', [
            'file' => $file,
            'collection' => 'posts',
        ], ['Accept' => 'application/json'])->json('data');

        $create = $this->postJson('/api/v1/posts', [
            'title' => 'With gallery image',
            'slug' => 'with-gallery-image',
            'content' => 'body',
            'status' => PostStatus::Draft->value,
            'thumbnail_media_id' => $media['id'],
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.thumbnail', $media['path']);

        $postId = $create->json('data.id');
        $this->deleteJson('/api/v1/posts/'.$postId)->assertNoContent();

        $this->assertDatabaseMissing('posts', ['id' => $postId]);
        Storage::disk('public')->assertExists($media['path']);
        $this->assertDatabaseHas('media', ['id' => $media['id']]);
    }

    public function test_cannot_upload_private_collection_through_library_api(): void
    {
        $this->actingAuthor();

        $this->post('/api/v1/media', [
            'file' => UploadedFile::fake()->image('card.jpg'),
            'collection' => 'medical_records',
        ], ['Accept' => 'application/json'])->assertStatus(422);
    }

    public function test_receptionist_cannot_access_media_library(): void
    {
        $user = User::factory()->admin(AdminRole::Receptionist)->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/media')->assertForbidden();
    }

    public function test_can_create_rename_and_delete_folders(): void
    {
        $this->actingAuthor();

        $created = $this->postJson('/api/v1/media/folders', [
            'name' => 'کمپین نوروز',
        ]);

        $created->assertCreated();
        $folderId = $created->json('data.id');

        $this->patchJson('/api/v1/media/folders/'.$folderId, [
            'name' => 'کمپین بهار',
        ])->assertOk()->assertJsonPath('data.name', 'کمپین بهار');

        $this->getJson('/api/v1/media/folders')->assertOk()
            ->assertJsonPath('data.0.id', $folderId);

        $this->deleteJson('/api/v1/media/folders/'.$folderId)->assertNoContent();
        $this->assertDatabaseMissing('media_folders', ['id' => $folderId]);
    }

    public function test_can_rename_media_file(): void
    {
        $this->actingAuthor();

        $media = $this->post('/api/v1/media', [
            'file' => UploadedFile::fake()->image('old-name.jpg'),
            'collection' => 'library',
        ], ['Accept' => 'application/json'])->json('data');

        $updated = $this->patchJson('/api/v1/media/'.$media['id'], [
            'name' => 'new-name',
        ]);

        $updated->assertOk()->assertJsonPath('data.name', 'new-name');
        Storage::disk('public')->assertExists($updated->json('data.path'));
    }

    public function test_file_service_builds_future_document_paths(): void
    {
        $path = app(FileService::class)->interpolate(
            config('media.collections.therapist_documents.path'),
            ['therapist_slug' => 'Ali Rezaei'],
        );

        $this->assertSame('documents/therapists/ali-rezaei', $path);

        $certPath = app(FileService::class)->interpolate(
            config('media.collections.workshop_certificates.path'),
            [
                'workshop_slug' => 'CBT Basics',
                'participant_id' => 'abc-123',
            ],
        );

        $this->assertSame('workshops/cbt-basics/certificates/abc-123', $certPath);
    }

    public function test_media_index_command_catalogs_existing_files(): void
    {
        Storage::disk('public')->put('posts/legacy.jpg', 'fake-image');

        $this->artisan('media:index')->assertSuccessful();

        $this->assertDatabaseHas('media', [
            'path' => 'posts/legacy.jpg',
            'collection' => 'posts',
        ]);
    }

    public function test_medical_record_images_are_stored_privately(): void
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

        $this->post('/api/v1/treatment-programs/'.$program->id.'/medical-record', [
            'record_number' => 'REC-200',
            'chief_complaints' => 'Anxiety',
            'images' => [UploadedFile::fake()->image('scan.jpg')],
        ], ['Accept' => 'application/json'])->assertCreated();

        $media = Media::query()->where('collection', 'medical_records')->first();
        $this->assertNotNull($media);
        $this->assertSame('local', $media->disk);
        $this->assertSame('private', $media->visibility);
        Storage::disk('local')->assertExists($media->path);
        $this->getJson('/api/v1/media')->assertOk()
            ->assertJsonPath('data.meta.total', 0);
    }

    public function test_can_upload_to_named_collections_and_folders(): void
    {
        $this->actingAuthor();

        $folderId = $this->postJson('/api/v1/media/folders', [
            'name' => 'آواتارها',
        ])->json('data.id');

        foreach (['doctor_avatars', 'posts', 'workshops', 'categories'] as $collection) {
            $response = $this->post('/api/v1/media', [
                'file' => UploadedFile::fake()->image($collection.'.png', 100, 100),
                'collection' => $collection,
                'folder_id' => $folderId,
            ], ['Accept' => 'application/json']);

            $response->assertCreated()
                ->assertJsonPath('data.collection', $collection)
                ->assertJsonPath('data.folder_id', $folderId);

            Storage::disk('public')->assertExists($response->json('data.path'));
        }
    }

    public function test_avatar_upload_does_not_collide_with_existing_cataloged_path(): void
    {
        $this->actingAuthor();

        Media::query()->create([
            'disk' => 'public',
            'path' => 'doctor_avatars/avatar.jpg',
            'collection' => 'doctor_avatars',
            'original_name' => 'avatar.jpg',
            'name' => 'avatar',
            'mime' => 'image/jpeg',
            'size' => 10,
            'visibility' => 'public',
        ]);

        $response = $this->post('/api/v1/media', [
            'file' => UploadedFile::fake()->image('avatar.jpg'),
            'collection' => 'doctor_avatars',
        ], ['Accept' => 'application/json']);

        $response->assertCreated()
            ->assertJsonPath('data.collection', 'doctor_avatars');

        $this->assertNotSame('doctor_avatars/avatar.jpg', $response->json('data.path'));
        Storage::disk('public')->assertExists($response->json('data.path'));
    }

    public function test_heic_avatar_is_rejected_with_clear_error(): void
    {
        $this->actingAuthor();

        $file = UploadedFile::fake()->create('photo.heic', 200, 'image/heic');

        $this->post('/api/v1/media', [
            'file' => $file,
            'collection' => 'doctor_avatars',
        ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'فرمت HEIC آیفون پشتیبانی نمی‌شود. عکس را به JPG یا PNG تبدیل کنید.']);
    }

    private function actingAuthor(): User
    {
        $author = User::factory()->admin(AdminRole::Author)->create();
        Sanctum::actingAs($author);

        return $author;
    }
}
