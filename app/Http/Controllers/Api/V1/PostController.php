<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Post\UpsertPostAction;
use App\Enums\PostStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Post\StorePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Http\Responses\ApiResponse;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Post::query()
            ->with(['author', 'category', 'tags'])
            ->withCount(['comments as comments_count' => fn ($q) => $q->approved()])
            ->withAvg(['comments as rating_avg' => fn ($q) => $q->approved()], 'rating');

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->query('category_id'));
        }

        if ($request->filled('tag_ids')) {
            $tagIds = (array) $request->query('tag_ids');
            $query->whereHas('tags', fn ($q) => $q->whereIn('tags.id', $tagIds));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $posts = $query
            ->orderBy($request->query('sort_by', 'created_at'), $request->query('sort_direction', 'desc'))
            ->paginate((int) $request->query('per_page', 15));

        return ApiResponse::success([
            'items' => PostResource::collection($posts->items()),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
            ],
        ]);
    }

    public function store(StorePostRequest $request, UpsertPostAction $action): JsonResponse
    {
        $data = $request->safe()->except(['thumbnail', 'thumbnail_media_id']);
        $data['author_id'] = $request->user()->id;
        $data['status'] = $data['status'] ?? PostStatus::Draft->value;
        $data['thumbnail_media_id'] = $request->validated('thumbnail_media_id');

        $post = $action->execute(
            $data,
            null,
            $request->file('thumbnail'),
        );

        return ApiResponse::created(
            PostResource::make($post),
            'Post created successfully.',
        );
    }

    public function show(Post $post): JsonResponse
    {
        $post->load([
            'author',
            'category',
            'tags',
            'comments' => fn ($q) => $q->approved()->orderByDesc('created_at'),
        ])
            ->loadCount(['comments as comments_count' => fn ($q) => $q->approved()])
            ->loadAvg(['comments as rating_avg' => fn ($q) => $q->approved()], 'rating');

        return ApiResponse::success(PostResource::make($post));
    }

    public function update(UpdatePostRequest $request, Post $post, UpsertPostAction $action): JsonResponse
    {
        $data = $request->safe()->except(['thumbnail', 'thumbnail_media_id']);
        $data['thumbnail_media_id'] = $request->validated('thumbnail_media_id');

        $post = $action->execute(
            $data,
            $post,
            $request->file('thumbnail'),
        );

        return ApiResponse::success(
            PostResource::make($post),
            'Post updated successfully.',
        );
    }

    public function destroy(Post $post): JsonResponse
    {
        $post->delete();

        return ApiResponse::noContent();
    }
}
