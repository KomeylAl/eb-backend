<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tag\StoreTagRequest;
use App\Http\Requests\Tag\UpdateTagRequest;
use App\Http\Resources\TagResource;
use App\Http\Responses\ApiResponse;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TagController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Tag::query()->withCount('posts');

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%");
            });
        }

        $tags = $query
            ->orderBy($request->query('sort_by', 'created_at'), $request->query('sort_direction', 'desc'))
            ->paginate((int) $request->query('per_page', 15));

        return ApiResponse::success([
            'items' => TagResource::collection($tags->items()),
            'meta' => [
                'current_page' => $tags->currentPage(),
                'last_page' => $tags->lastPage(),
                'per_page' => $tags->perPage(),
                'total' => $tags->total(),
            ],
        ]);
    }

    public function store(StoreTagRequest $request): JsonResponse
    {
        $data = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('tags', 'public');
        }

        $tag = Tag::query()->create($data);

        return ApiResponse::created(
            TagResource::make($tag),
            'Tag created successfully.',
        );
    }

    public function show(Tag $tag): JsonResponse
    {
        $tag->loadCount('posts');

        return ApiResponse::success(TagResource::make($tag));
    }

    public function update(UpdateTagRequest $request, Tag $tag): JsonResponse
    {
        $data = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            if ($tag->image) {
                Storage::disk('public')->delete($tag->image);
            }
            $data['image'] = $request->file('image')->store('tags', 'public');
        }

        $tag->update($data);

        return ApiResponse::success(
            TagResource::make($tag->fresh()->loadCount('posts')),
            'Tag updated successfully.',
        );
    }

    public function destroy(Tag $tag): JsonResponse
    {
        if ($tag->image) {
            Storage::disk('public')->delete($tag->image);
        }

        $tag->delete();

        return ApiResponse::noContent();
    }
}
