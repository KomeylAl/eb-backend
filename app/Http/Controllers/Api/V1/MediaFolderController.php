<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Media\StoreMediaFolderRequest;
use App\Http\Requests\Media\UpdateMediaFolderRequest;
use App\Http\Resources\MediaFolderResource;
use App\Http\Responses\ApiResponse;
use App\Models\MediaFolder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MediaFolderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = MediaFolder::query()->orderBy('name');

        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->query('parent_id'));
        } elseif ($request->boolean('roots', true) && ! $request->boolean('all')) {
            $query->whereNull('parent_id');
        }

        return ApiResponse::success(
            MediaFolderResource::collection($query->get()),
        );
    }

    public function store(StoreMediaFolderRequest $request): JsonResponse
    {
        $name = $request->validated('name');
        $parentId = $request->validated('parent_id');

        $folder = MediaFolder::query()->create([
            'name' => $name,
            'slug' => $this->uniqueSlug($name, $parentId),
            'parent_id' => $parentId,
        ]);

        return ApiResponse::created(
            MediaFolderResource::make($folder),
            'Folder created successfully.',
        );
    }

    public function update(UpdateMediaFolderRequest $request, MediaFolder $mediaFolder): JsonResponse
    {
        $data = $request->validated();

        if (array_key_exists('parent_id', $data) && $data['parent_id'] === $mediaFolder->id) {
            return ApiResponse::error('A folder cannot be its own parent.', 422);
        }

        if (isset($data['name']) && $data['name'] !== $mediaFolder->name) {
            $parentId = array_key_exists('parent_id', $data) ? $data['parent_id'] : $mediaFolder->parent_id;
            $data['slug'] = $this->uniqueSlug($data['name'], $parentId, $mediaFolder->id);
        }

        $mediaFolder->update($data);

        return ApiResponse::success(
            MediaFolderResource::make($mediaFolder->refresh()),
            'Folder updated successfully.',
        );
    }

    public function destroy(MediaFolder $mediaFolder): JsonResponse
    {
        if ($mediaFolder->children()->exists()) {
            return ApiResponse::error('Folder has subfolders and cannot be deleted.', 422);
        }

        $mediaFolder->delete();

        return ApiResponse::noContent();
    }

    private function uniqueSlug(string $name, ?string $parentId, ?string $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'folder';
        $slug = $base;
        $i = 2;

        while (
            MediaFolder::query()
                ->where('parent_id', $parentId)
                ->where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
