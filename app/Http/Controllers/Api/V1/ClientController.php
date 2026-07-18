<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Client\CreateClientAction;
use App\Actions\Client\UpdateClientAction;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreClientRequest;
use App\Http\Requests\Client\UpdateClientRequest;
use App\Http\Resources\ClientResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query()->where('type', UserType::Client);

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
        $clients = $query->paginate($perPage);

        return ApiResponse::success([
            'items' => ClientResource::collection($clients)->resolve(),
            'meta' => [
                'current_page' => $clients->currentPage(),
                'last_page' => $clients->lastPage(),
                'per_page' => $clients->perPage(),
                'total' => $clients->total(),
            ],
        ]);
    }

    public function store(StoreClientRequest $request, CreateClientAction $action): JsonResponse
    {
        $client = $action->execute($request->validated());

        return ApiResponse::created(ClientResource::make($client), 'Client created successfully.');
    }

    public function show(User $client): JsonResponse
    {
        abort_unless($client->type === UserType::Client, 404);

        return ApiResponse::success(ClientResource::make($client));
    }

    public function update(UpdateClientRequest $request, User $client, UpdateClientAction $action): JsonResponse
    {
        abort_unless($client->type === UserType::Client, 404);

        $client = $action->execute($client, $request->validated());

        return ApiResponse::success(ClientResource::make($client), 'Client updated successfully.');
    }

    public function destroy(User $client): JsonResponse
    {
        abort_unless($client->type === UserType::Client, 404);

        $client->delete();

        return ApiResponse::noContent();
    }
}
