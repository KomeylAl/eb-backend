<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\WorkshopType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workshop\StoreWorkshopRequest;
use App\Http\Requests\Workshop\UpdateWorkshopRequest;
use App\Http\Resources\WorkshopResource;
use App\Http\Responses\ApiResponse;
use App\Models\Workshop;
use App\Services\FileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkshopController extends Controller
{
    public function __construct(private FileService $files) {}

    public function index(Request $request): JsonResponse
    {
        $query = Workshop::query();

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $type = WorkshopType::normalize((string) $request->query('type'));
            $allowed = array_column(WorkshopType::cases(), 'value');
            if ($type && in_array($type, $allowed, true)) {
                $query->where('type', $type);
            }
        }

        if ($request->filled('start_date')) {
            $query->whereDate('start_date', '>=', $request->query('start_date'));
        }

        $workshops = $query
            ->orderBy($request->query('sort_by', 'created_at'), $request->query('sort_direction', 'desc'))
            ->paginate((int) $request->query('per_page', 15));

        return ApiResponse::success([
            'items' => WorkshopResource::collection($workshops->items()),
            'meta' => [
                'current_page' => $workshops->currentPage(),
                'last_page' => $workshops->lastPage(),
                'per_page' => $workshops->perPage(),
                'total' => $workshops->total(),
            ],
        ]);
    }

    public function store(StoreWorkshopRequest $request): JsonResponse
    {
        $data = $request->safe()->except(['image', 'image_media_id']);

        $path = $this->files->assign(
            'workshops',
            $request->file('image'),
            $request->validated('image_media_id'),
            uploadedBy: $request->user()?->id,
        );

        if ($path) {
            $data['img_path'] = $path;
        }

        $workshop = Workshop::query()->create($data);

        return ApiResponse::created(
            WorkshopResource::make($workshop),
            'Workshop created successfully.',
        );
    }

    public function show(Workshop $workshop): JsonResponse
    {
        $workshop->load([
            'sessions',
            'participants',
            'comments' => fn ($q) => $q->approved()->orderByDesc('created_at'),
        ])
            ->loadCount(['comments as comments_count' => fn ($q) => $q->approved()])
            ->loadAvg(['comments as rating_avg' => fn ($q) => $q->approved()], 'rating');

        return ApiResponse::success(WorkshopResource::make($workshop));
    }

    public function update(UpdateWorkshopRequest $request, Workshop $workshop): JsonResponse
    {
        $data = $request->safe()->except(['image', 'image_media_id']);

        $path = $this->files->assign(
            'workshops',
            $request->file('image'),
            $request->validated('image_media_id'),
            uploadedBy: $request->user()?->id,
        );

        if ($path) {
            $data['img_path'] = $path;
        }

        $workshop->update($data);

        return ApiResponse::success(
            WorkshopResource::make($workshop->fresh()),
            'Workshop updated successfully.',
        );
    }

    public function destroy(Workshop $workshop): JsonResponse
    {
        $workshop->delete();

        return ApiResponse::noContent();
    }
}
