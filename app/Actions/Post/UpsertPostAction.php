<?php

namespace App\Actions\Post;

use App\Models\Post;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UpsertPostAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, ?Post $post = null, ?UploadedFile $thumbnail = null): Post
    {
        return DB::transaction(function () use ($data, $post, $thumbnail) {
            $payload = collect($data)->only([
                'author_id',
                'category_id',
                'title',
                'slug',
                'excerpt',
                'content',
                'status',
                'published_at',
            ])->all();

            if ($thumbnail) {
                if ($post?->thumbnail) {
                    Storage::disk('public')->delete($post->thumbnail);
                }
                $payload['thumbnail'] = $thumbnail->store('posts', 'public');
            }

            if ($post) {
                $post->update($payload);
            } else {
                $post = Post::query()->create($payload);
            }

            if (array_key_exists('tag_ids', $data)) {
                $post->tags()->detach();
                foreach ($data['tag_ids'] ?? [] as $tagId) {
                    $post->tags()->attach($tagId, ['id' => (string) Str::uuid()]);
                }
            }

            return $post->load(['author', 'category', 'tags'])->loadCount('comments');
        });
    }
}
