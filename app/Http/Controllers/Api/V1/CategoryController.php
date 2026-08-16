<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Responses\ApiResponse;
use App\Models\Category;
use App\Services\FileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(private FileService $files) {}

    public function index(Request $request): JsonResponse
    {
        $query = Category::query()->withCount('posts');

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%");
            });
        }

        $categories = $query
            ->orderBy($request->query('sort_by', 'created_at'), $request->query('sort_direction', 'desc'))
            ->paginate((int) $request->query('per_page', 15));

        return ApiResponse::success([
            'items' => CategoryResource::collection($categories->items()),
            'meta' => [
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
            ],
        ]);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $data = $request->safe()->except(['image', 'image_media_id']);

        $path = $this->files->assign(
            'categories',
            $request->file('image'),
            $request->validated('image_media_id'),
            uploadedBy: $request->user()?->id,
        );

        if ($path) {
            $data['image'] = $path;
        }

        $category = Category::query()->create($data);

        return ApiResponse::created(
            CategoryResource::make($category),
            'Category created successfully.',
        );
    }

    public function show(Category $category): JsonResponse
    {
        $category->loadCount('posts');

        return ApiResponse::success(CategoryResource::make($category));
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $data = $request->safe()->except(['image', 'image_media_id']);

        $path = $this->files->assign(
            'categories',
            $request->file('image'),
            $request->validated('image_media_id'),
            uploadedBy: $request->user()?->id,
        );

        if ($path) {
            $data['image'] = $path;
        }

        $category->update($data);

        return ApiResponse::success(
            CategoryResource::make($category->fresh()->loadCount('posts')),
            'Category updated successfully.',
        );
    }

    public function destroy(Category $category): JsonResponse
    {
        $category->delete();

        return ApiResponse::noContent();
    }
}
