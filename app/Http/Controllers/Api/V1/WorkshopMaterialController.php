<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ResourceType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workshop\StoreWorkshopMaterialRequest;
use App\Http\Requests\Workshop\UpdateWorkshopMaterialRequest;
use App\Http\Resources\WorkshopMaterialResource;
use App\Http\Responses\ApiResponse;
use App\Models\Workshop;
use App\Models\WorkshopMaterial;
use App\Services\FileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WorkshopMaterialController extends Controller
{
    public function __construct(private FileService $files) {}

    public function index(Workshop $workshop): JsonResponse
    {
        $materials = $workshop->materials()
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->get();

        return ApiResponse::success(WorkshopMaterialResource::collection($materials));
    }

    public function store(StoreWorkshopMaterialRequest $request, Workshop $workshop): JsonResponse
    {
        $type = ResourceType::from($request->validated('type'));
        $data = [
            'workshop_id' => $workshop->id,
            'title' => $request->validated('title'),
            'type' => $type,
            'description' => $request->validated('description'),
            'link' => null,
            'file_path' => null,
            'original_name' => null,
            'sort_order' => (int) ($request->validated('sort_order') ?? 0),
        ];

        if ($type === ResourceType::Link) {
            $data['link'] = $request->validated('link');
        }

        if ($type === ResourceType::File && $request->hasFile('file')) {
            $file = $request->file('file');
            $media = $this->files->store(
                $file,
                'workshop_materials',
                context: ['workshop_slug' => $workshop->slug],
                uploadedBy: $request->user()?->id,
            );
            $data['file_path'] = $media->path;
            $data['original_name'] = $file->getClientOriginalName();
        }

        $material = WorkshopMaterial::query()->create($data);

        return ApiResponse::created(
            WorkshopMaterialResource::make($material),
            'Workshop material created successfully.',
        );
    }

    public function update(
        UpdateWorkshopMaterialRequest $request,
        Workshop $workshop,
        WorkshopMaterial $material,
    ): JsonResponse {
        abort_unless($material->workshop_id === $workshop->id, 404);

        $data = $request->safe()->only(['title', 'description', 'sort_order']);

        if ($request->filled('type')) {
            $type = ResourceType::from($request->validated('type'));
            $data['type'] = $type;

            if ($type === ResourceType::Link) {
                $data['link'] = $request->validated('link');
                if ($material->file_path) {
                    $material->deleteFile();
                }
                $data['file_path'] = null;
                $data['original_name'] = null;
            }

            if ($type === ResourceType::File) {
                $data['link'] = null;
            }
        } elseif ($material->type === ResourceType::Link && $request->filled('link')) {
            $data['link'] = $request->validated('link');
        }

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            if ($material->file_path) {
                $material->deleteFile();
            }
            $media = $this->files->store(
                $file,
                'workshop_materials',
                context: ['workshop_slug' => $workshop->slug],
                uploadedBy: $request->user()?->id,
            );
            $data['type'] = ResourceType::File;
            $data['file_path'] = $media->path;
            $data['original_name'] = $file->getClientOriginalName();
            $data['link'] = null;
        }

        $material->update($data);

        return ApiResponse::success(
            WorkshopMaterialResource::make($material->fresh()),
            'Workshop material updated successfully.',
        );
    }

    public function destroy(Workshop $workshop, WorkshopMaterial $material): JsonResponse
    {
        abort_unless($material->workshop_id === $workshop->id, 404);

        $material->deleteFile();
        $material->delete();

        return ApiResponse::noContent();
    }

    public function download(
        Workshop $workshop,
        WorkshopMaterial $material,
    ): StreamedResponse|JsonResponse {
        abort_unless($material->workshop_id === $workshop->id, 404);

        if ($material->type !== ResourceType::File || ! $material->file_path) {
            return ApiResponse::error('This material has no downloadable file.', 404);
        }

        $disk = $material->disk();
        if (! Storage::disk($disk)->exists($material->file_path)) {
            return ApiResponse::error('File not found.', 404);
        }

        return Storage::disk($disk)->download(
            $material->file_path,
            $material->original_name ?: basename($material->file_path),
        );
    }
}
