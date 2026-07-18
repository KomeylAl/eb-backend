<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Admin\CreateAdminAction;
use App\Actions\Admin\UpdateAdminAction;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminRequest;
use App\Http\Requests\Admin\UpdateAdminRequest;
use App\Http\Resources\AdminResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query()->where('type', UserType::Admin);

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->query('sort_by', 'created_at');
        $sortDirection = strtolower($request->query('sort_direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDirection);

        $perPage = max(1, min((int) $request->query('per_page', 10), 100));
        $admins = $query->paginate($perPage);

        return ApiResponse::success([
            'items' => AdminResource::collection($admins)->resolve(),
            'meta' => [
                'current_page' => $admins->currentPage(),
                'last_page' => $admins->lastPage(),
                'per_page' => $admins->perPage(),
                'total' => $admins->total(),
            ],
        ]);
    }

    public function store(StoreAdminRequest $request, CreateAdminAction $action): JsonResponse
    {
        $admin = $action->execute($request->validated());

        return ApiResponse::created(AdminResource::make($admin), 'Admin created successfully.');
    }

    public function show(User $admin): JsonResponse
    {
        abort_unless($admin->type === UserType::Admin, 404);

        return ApiResponse::success(AdminResource::make($admin));
    }

    public function update(UpdateAdminRequest $request, User $admin, UpdateAdminAction $action): JsonResponse
    {
        abort_unless($admin->type === UserType::Admin, 404);

        $admin = $action->execute($admin, $request->validated());

        return ApiResponse::success(AdminResource::make($admin), 'Admin updated successfully.');
    }

    public function destroy(User $admin): JsonResponse
    {
        abort_unless($admin->type === UserType::Admin, 404);

        $admin->delete();

        return ApiResponse::noContent();
    }
}
