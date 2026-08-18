<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\PostStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkshopAndBlogTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_can_list_posts_and_author_can_create(): void
    {
        $this->getJson('/api/v1/posts')->assertOk();

        $author = User::factory()->admin(AdminRole::Author)->create();
        Sanctum::actingAs($author);

        $response = $this->postJson('/api/v1/posts', [
            'title' => 'Hello',
            'slug' => 'hello',
            'excerpt' => 'excerpt',
            'content' => 'body content',
            'status' => PostStatus::Published->value,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.slug', 'hello');
    }

    public function test_admin_can_create_workshop(): void
    {
        $admin = User::factory()->admin(AdminRole::Boss)->create();
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/workshops', [
            'title' => 'Workshop A',
            'slug' => 'workshop-a',
            'type' => 'webinar',
            'excerpt' => 'short',
            'content' => 'long content',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.slug', 'workshop-a')
            ->assertJsonPath('data.type', 'webinar');
    }

    public function test_public_can_filter_workshops_by_type(): void
    {
        $admin = User::factory()->admin(AdminRole::Boss)->create();
        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/workshops', [
            'title' => 'General workshop',
            'slug' => 'general-workshop',
            'type' => 'general',
        ])->assertCreated();

        $this->postJson('/api/v1/workshops', [
            'title' => 'Specialized workshop',
            'slug' => 'specialized-workshop',
            'type' => 'specialized',
        ])->assertCreated();

        $this->postJson('/api/v1/workshops', [
            'title' => 'Seminar event',
            'slug' => 'seminar-event',
            'type' => 'seminar',
        ])->assertCreated();

        $this->getJson('/api/v1/workshops?type=specialized')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonPath('data.items.0.slug', 'specialized-workshop');

        // Legacy alias from older menu links
        $this->getJson('/api/v1/workshops?type=special')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonPath('data.items.0.type', 'specialized');

        $this->getJson('/api/v1/workshops?type=seminar')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonPath('data.items.0.slug', 'seminar-event');
    }
}
