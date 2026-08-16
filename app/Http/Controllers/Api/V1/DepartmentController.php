<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Department\StoreDepartmentRequest;
use App\Http\Requests\Department\UpdateDepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Http\Responses\ApiResponse;
use App\Models\Department;
use App\Services\FileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function __construct(private FileService $files) {}

    public function index(Request $request): JsonResponse
    {
        $query = Department::query();

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->query('sort_by', 'created_at');
        $sortDirection = strtolower($request->query('sort_direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDirection);

        $perPage = max(1, min((int) $request->query('per_page', 10), 100));
        $departments = $query->paginate($perPage);

        return ApiResponse::success([
            'items' => DepartmentResource::collection($departments)->resolve(),
            'meta' => [
                'current_page' => $departments->currentPage(),
                'last_page' => $departments->lastPage(),
                'per_page' => $departments->perPage(),
                'total' => $departments->total(),
            ],
        ]);
    }

    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $data = $request->safe()->except(['thumbnail', 'thumbnail_media_id']);

        $path = $this->files->assign(
            'departments',
            $request->file('thumbnail'),
            $request->validated('thumbnail_media_id'),
            uploadedBy: $request->user()?->id,
        );

        if ($path) {
            $data['thumbnail'] = $path;
        }

        $department = Department::query()->create($data);

        return ApiResponse::created(
            DepartmentResource::make($department),
            'Department created successfully.',
        );
    }

    public function show(Department $department): JsonResponse
    {
        return ApiResponse::success(DepartmentResource::make($department));
    }

    public function update(UpdateDepartmentRequest $request, Department $department): JsonResponse
    {
        $data = $request->safe()->except(['thumbnail', 'thumbnail_media_id']);

        $path = $this->files->assign(
            'departments',
            $request->file('thumbnail'),
            $request->validated('thumbnail_media_id'),
            uploadedBy: $request->user()?->id,
        );

        if ($path) {
            $data['thumbnail'] = $path;
        }

        $department->update($data);

        return ApiResponse::success(
            DepartmentResource::make($department->refresh()),
            'Department updated successfully.',
        );
    }

    public function destroy(Department $department): JsonResponse
    {
        $department->delete();

        return ApiResponse::noContent();
    }
}
