<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Media\StoreMediaRequest;
use App\Http\Requests\Media\UpdateMediaRequest;
use App\Http\Resources\MediaResource;
use App\Http\Responses\ApiResponse;
use App\Models\Media;
use App\Services\FileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    public function __construct(private FileService $files) {}

    public function collections(): JsonResponse
    {
        $items = collect(config('media.collections', []))
            ->filter(fn (array $collection) => ! empty($collection['library']))
            ->map(fn (array $collection, string $key) => [
                'key' => $key,
                'label' => $collection['label'] ?? $key,
                'max_kb' => $collection['max_kb'] ?? null,
                'extensions' => $collection['extensions'] ?? [],
            ])
            ->values();

        return ApiResponse::success($items);
    }

    public function index(Request $request): JsonResponse
    {
        $libraryKeys = collect(config('media.collections', []))
            ->filter(fn (array $collection) => ! empty($collection['library']))
            ->keys();

        $query = Media::query()->whereIn('collection', $libraryKeys);

        if ($request->filled('collection')) {
            $collection = $request->query('collection');
            abort_unless($libraryKeys->contains($collection), 403);
            $query->where('collection', $collection);
        }

        if ($request->filled('folder_id')) {
            $query->where('folder_id', $request->query('folder_id'));
        } elseif ($request->boolean('uncategorized')) {
            $query->whereNull('folder_id');
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('original_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('mime')) {
            $mime = $request->query('mime');
            if ($mime === 'image') {
                $query->where('mime', 'like', 'image/%');
            } else {
                $query->where('mime', 'like', $mime.'%');
            }
        }

        $media = $query
            ->orderBy($request->query('sort_by', 'created_at'), $request->query('sort_direction', 'desc'))
            ->paginate((int) $request->query('per_page', 24));

        return ApiResponse::success([
            'items' => MediaResource::collection($media->items()),
            'meta' => [
                'current_page' => $media->currentPage(),
                'last_page' => $media->lastPage(),
                'per_page' => $media->perPage(),
                'total' => $media->total(),
            ],
        ]);
    }

    public function store(StoreMediaRequest $request): JsonResponse
    {
        $media = $this->files->store(
            $request->file('file'),
            $request->validated('collection'),
            $request->validated('context') ?? [],
            $request->validated('name'),
            $request->validated('folder_id'),
            $request->user()?->id,
        );

        return ApiResponse::created(
            MediaResource::make($media),
            'File uploaded successfully.',
        );
    }

    public function update(UpdateMediaRequest $request, Media $media): JsonResponse
    {
        $this->assertLibrary($media);

        if ($request->filled('name') && $request->validated('name') !== $media->name) {
            $media = $this->files->rename($media, $request->validated('name'));
        }

        if ($request->exists('folder_id')) {
            $media = $this->files->moveToFolder($media, $request->validated('folder_id'));
        }

        return ApiResponse::success(
            MediaResource::make($media),
            'Media updated successfully.',
        );
    }

    public function destroy(Media $media): JsonResponse
    {
        $this->assertLibrary($media);
        $this->files->delete($media);

        return ApiResponse::noContent();
    }

    public function file(Media $media): StreamedResponse
    {
        abort_unless(Storage::disk($media->disk)->exists($media->path), 404);

        $downloadName = $media->original_name ?: ($media->name.'.'.pathinfo($media->path, PATHINFO_EXTENSION));

        return Storage::disk($media->disk)->response(
            $media->path,
            $downloadName,
            ['Content-Type' => $media->mime ?: 'application/octet-stream'],
        );
    }

    private function assertLibrary(Media $media): void
    {
        abort_unless($this->files->isLibraryCollection($media->collection), 403);
    }
}
