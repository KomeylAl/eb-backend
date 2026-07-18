<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Department\StoreDepartmentRequest;
use App\Http\Requests\Department\UpdateDepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Http\Responses\ApiResponse;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DepartmentController extends Controller
{
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
        $data = $request->validated();

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = $request->file('thumbnail')->store('department_images', 'public');
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
        $data = $request->validated();

        if ($request->hasFile('thumbnail')) {
            if ($department->thumbnail) {
                Storage::disk('public')->delete($department->thumbnail);
            }
            $data['thumbnail'] = $request->file('thumbnail')->store('department_images', 'public');
        }

        $department->update($data);

        return ApiResponse::success(
            DepartmentResource::make($department->refresh()),
            'Department updated successfully.',
        );
    }

    public function destroy(Department $department): JsonResponse
    {
        if ($department->thumbnail) {
            Storage::disk('public')->delete($department->thumbnail);
        }

        $department->delete();

        return ApiResponse::noContent();
    }
}
