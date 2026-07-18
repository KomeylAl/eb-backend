<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Responses\ApiResponse;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
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
        $data = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
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
        $data = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }
            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        $category->update($data);

        return ApiResponse::success(
            CategoryResource::make($category->fresh()->loadCount('posts')),
            'Category updated successfully.',
        );
    }

    public function destroy(Category $category): JsonResponse
    {
        if ($category->image) {
            Storage::disk('public')->delete($category->image);
        }

        $category->delete();

        return ApiResponse::noContent();
    }
}
