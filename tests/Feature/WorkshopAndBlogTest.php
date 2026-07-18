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
            'excerpt' => 'short',
            'content' => 'long content',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.slug', 'workshop-a');
    }
}
