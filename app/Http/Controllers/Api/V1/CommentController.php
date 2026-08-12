<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Comment\StoreCommentAction;
use App\Enums\CommentableType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Comment\StoreCommentRequest;
use App\Http\Requests\Comment\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use App\Http\Responses\ApiResponse;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CommentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $isAdmin = $request->user('sanctum')?->isAdmin() === true;

        if (! $isAdmin) {
            $request->validate([
                'commentable_type' => ['required', 'string', Rule::enum(CommentableType::class)],
                'commentable_id' => ['required', 'uuid'],
            ]);
        }

        $query = Comment::query()->with('user');

        if ($request->filled('commentable_type')) {
            $type = CommentableType::from($request->query('commentable_type'));
            $query->where('commentable_type', $type->modelClass());
        }

        if ($request->filled('commentable_id')) {
            $query->where('commentable_id', $request->query('commentable_id'));
        }

        if ($isAdmin) {
            if ($request->filled('approved')) {
                $query->where('approved', filter_var($request->query('approved'), FILTER_VALIDATE_BOOLEAN));
            }

            if ($request->filled('phone')) {
                $query->where('phone', 'like', '%'.$request->query('phone').'%');
            }

            if ($request->filled('search')) {
                $search = $request->query('search');
                $query->where(function ($builder) use ($search) {
                    $builder->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('body', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            }
        } else {
            $query->approved();
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

    public function store(StoreCommentRequest $request, StoreCommentAction $action): JsonResponse
    {
        $comment = $action->execute(
            $request->validated(),
            $request->user('sanctum'),
        );

        return ApiResponse::created(
            CommentResource::make($comment),
            'Comment created successfully.',
        );
    }

    public function mine(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $comments = Comment::query()
            ->with('user')
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id);

                if (filled($user->phone)) {
                    $query->orWhere('phone', $user->phone);
                }
            })
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

    public function indexForDoctor(Request $request): JsonResponse
    {
        /** @var User $doctor */
        $doctor = $request->user();

        $comments = Comment::query()
            ->with('user')
            ->where('commentable_type', User::class)
            ->where('commentable_id', $doctor->id)
            ->approved()
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

    public function approve(Comment $comment): JsonResponse
    {
        $comment->update(['approved' => true]);

        return ApiResponse::success(
            CommentResource::make($comment->fresh()->load('user')),
            'Comment approved successfully.',
        );
    }

    public function unapprove(Comment $comment): JsonResponse
    {
        $comment->update(['approved' => false]);

        return ApiResponse::success(
            CommentResource::make($comment->fresh()->load('user')),
            'Comment unapproved successfully.',
        );
    }

    public function destroy(Comment $comment): JsonResponse
    {
        $comment->delete();

        return ApiResponse::noContent();
    }
}
