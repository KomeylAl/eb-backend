<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    protected $model = Comment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => fake()->numerify('09#########'),
            'body' => fake()->paragraph(),
            'rating' => fake()->numberBetween(1, 5),
            'approved' => false,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'approved' => true,
        ]);
    }

    public function forDoctor(User $doctor): static
    {
        return $this->state(fn () => [
            'commentable_type' => User::class,
            'commentable_id' => $doctor->id,
        ]);
    }

    public function forPost(Post $post): static
    {
        return $this->state(fn () => [
            'commentable_type' => Post::class,
            'commentable_id' => $post->id,
        ]);
    }

    public function forWorkshop(Workshop $workshop): static
    {
        return $this->state(fn () => [
            'commentable_type' => Workshop::class,
            'commentable_id' => $workshop->id,
        ]);
    }
}
