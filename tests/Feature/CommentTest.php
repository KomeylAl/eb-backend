<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\PostStatus;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_submit_comment_for_doctor_post_and_workshop(): void
    {
        $doctor = User::factory()->doctor()->create();
        $author = User::factory()->admin(AdminRole::Author)->create();
        $post = Post::query()->create([
            'author_id' => $author->id,
            'title' => 'Blog post',
            'slug' => 'blog-post',
            'content' => 'Content',
            'status' => PostStatus::Published,
        ]);
        $workshop = Workshop::query()->create([
            'title' => 'Workshop',
            'slug' => 'workshop',
            'content' => 'Workshop content',
        ]);

        foreach ([
            ['commentable_type' => 'doctor', 'commentable_id' => $doctor->id],
            ['commentable_type' => 'post', 'commentable_id' => $post->id],
            ['commentable_type' => 'workshop', 'commentable_id' => $workshop->id],
        ] as $target) {
            $response = $this->postJson('/api/v1/comments', [
                ...$target,
                'first_name' => 'Ali',
                'last_name' => 'Rezaei',
                'phone' => '09121234567',
                'body' => 'Great experience',
                'rating' => 5,
            ]);

            $response->assertCreated()
                ->assertJsonPath('data.approved', false)
                ->assertJsonPath('data.rating', 5)
                ->assertJsonPath('data.author_name', 'Ali Rezaei')
                ->assertJsonMissingPath('data.phone');
        }

        $this->assertDatabaseCount('comments', 3);
    }

    public function test_public_list_only_returns_approved_comments_and_hides_phone(): void
    {
        $doctor = User::factory()->doctor()->create();

        Comment::factory()->forDoctor($doctor)->approved()->create([
            'first_name' => 'Approved',
            'last_name' => 'User',
            'phone' => '09120000001',
            'body' => 'Visible',
            'rating' => 4,
        ]);
        Comment::factory()->forDoctor($doctor)->create([
            'first_name' => 'Pending',
            'last_name' => 'User',
            'phone' => '09120000002',
            'body' => 'Hidden',
            'rating' => 2,
        ]);

        $response = $this->getJson('/api/v1/comments?commentable_type=doctor&commentable_id='.$doctor->id);

        $response->assertOk()
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonPath('data.items.0.body', 'Visible')
            ->assertJsonMissingPath('data.items.0.phone');
    }

    public function test_admin_can_approve_comment_and_see_phone(): void
    {
        $admin = User::factory()->admin(AdminRole::Boss)->create();
        $doctor = User::factory()->doctor()->create();
        $comment = Comment::factory()->forDoctor($doctor)->create([
            'phone' => '09123334455',
            'approved' => false,
        ]);

        Sanctum::actingAs($admin);

        $this->patchJson('/api/v1/comments/'.$comment->id.'/approve')
            ->assertOk()
            ->assertJsonPath('data.approved', true)
            ->assertJsonPath('data.phone', '09123334455');

        $this->getJson('/api/v1/comments')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonPath('data.items.0.phone', '09123334455');
    }

    public function test_doctor_can_see_only_own_approved_comments(): void
    {
        $doctor = User::factory()->doctor()->create();
        $otherDoctor = User::factory()->doctor()->create();

        Comment::factory()->forDoctor($doctor)->approved()->create(['body' => 'Mine']);
        Comment::factory()->forDoctor($doctor)->create(['body' => 'Pending']);
        Comment::factory()->forDoctor($otherDoctor)->approved()->create(['body' => 'Other']);

        Sanctum::actingAs($doctor);

        $this->getJson('/api/v1/doctor/comments')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonPath('data.items.0.body', 'Mine');
    }

    public function test_client_can_list_own_comments_by_phone_link(): void
    {
        $client = User::factory()->client()->create([
            'phone' => '09125556677',
        ]);
        $doctor = User::factory()->doctor()->create();

        $this->postJson('/api/v1/comments', [
            'commentable_type' => 'doctor',
            'commentable_id' => $doctor->id,
            'first_name' => 'Sara',
            'last_name' => 'Ahmadi',
            'phone' => '09125556677',
            'body' => 'My earlier comment',
            'rating' => 3,
        ])->assertCreated()
            ->assertJsonPath('data.user_id', $client->id);

        Sanctum::actingAs($client);

        $this->getJson('/api/v1/comments/mine')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonPath('data.items.0.body', 'My earlier comment');
    }

    public function test_doctor_show_includes_only_approved_comments_and_rating_avg(): void
    {
        $doctor = User::factory()->doctor()->create();

        Comment::factory()->forDoctor($doctor)->approved()->create(['rating' => 5]);
        Comment::factory()->forDoctor($doctor)->approved()->create(['rating' => 3]);
        Comment::factory()->forDoctor($doctor)->create(['rating' => 1]);

        $this->getJson('/api/v1/doctors/'.$doctor->id)
            ->assertOk()
            ->assertJsonPath('data.comments_count', 2)
            ->assertJsonPath('data.rating_avg', 4)
            ->assertJsonCount(2, 'data.comments');
    }

    public function test_cannot_force_approved_on_public_create(): void
    {
        $doctor = User::factory()->doctor()->create();

        $this->postJson('/api/v1/comments', [
            'commentable_type' => 'doctor',
            'commentable_id' => $doctor->id,
            'first_name' => 'Ali',
            'last_name' => 'Rezaei',
            'phone' => '09121234567',
            'body' => 'Trying to auto approve',
            'rating' => 5,
            'approved' => true,
        ])->assertCreated()
            ->assertJsonPath('data.approved', false);
    }

    public function test_rating_must_be_between_one_and_five(): void
    {
        $doctor = User::factory()->doctor()->create();

        $this->postJson('/api/v1/comments', [
            'commentable_type' => 'doctor',
            'commentable_id' => $doctor->id,
            'first_name' => 'Ali',
            'last_name' => 'Rezaei',
            'phone' => '09121234567',
            'body' => 'Bad rating',
            'rating' => 6,
        ])->assertStatus(422);
    }
}
