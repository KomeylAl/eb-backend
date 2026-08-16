<?php

namespace App\Actions\Post;

use App\Models\Post;
use App\Services\FileService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UpsertPostAction
{
    public function __construct(private FileService $files) {}

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

            $thumbnailPath = $this->files->assign(
                'posts',
                $thumbnail,
                $data['thumbnail_media_id'] ?? null,
            );

            if ($thumbnailPath) {
                $payload['thumbnail'] = $thumbnailPath;
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
