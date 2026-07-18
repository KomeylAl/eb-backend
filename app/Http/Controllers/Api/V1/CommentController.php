<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Comment\StoreCommentRequest;
use App\Http\Requests\Comment\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use App\Http\Responses\ApiResponse;
use App\Models\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Comment::query()->with('user');

        if ($request->filled('post_id')) {
            $query->where('post_id', $request->query('post_id'));
        }

        if ($request->filled('approved')) {
            $query->where('approved', filter_var($request->query('approved'), FILTER_VALIDATE_BOOLEAN));
        }

        $comments = $query
            ->orderByDesc('created_at')
            ->paginate((int) $request->query('per_page', 20));

        return ApiResponse::success([
            'items' => CommentResource::collection($comments->items()),
            'meta' => [
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
                'per_page' => $comments->perPage(),
                'total' => $comments->total(),
            ],
        ]);
    }

    public function store(StoreCommentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()?->id;
        $data['approved'] = $data['approved'] ?? false;

        $comment = Comment::query()->create($data);

        return ApiResponse::created(
            CommentResource::make($comment->load('user')),
            'Comment created successfully.',
        );
    }

    public function show(Comment $comment): JsonResponse
    {
        return ApiResponse::success(CommentResource::make($comment->load('user')));
    }

    public function update(UpdateCommentRequest $request, Comment $comment): JsonResponse
    {
        $comment->update($request->validated());

        return ApiResponse::success(
            CommentResource::make($comment->fresh()->load('user')),
            'Comment updated successfully.',
        );
    }

    public function destroy(Comment $comment): JsonResponse
    {
        $comment->delete();

        return ApiResponse::noContent();
    }
}
