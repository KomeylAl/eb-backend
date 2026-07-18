<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Workshop\StoreWorkshopSessionRequest;
use App\Http\Requests\Workshop\UpdateWorkshopSessionRequest;
use App\Http\Resources\WorkshopSessionResource;
use App\Http\Responses\ApiResponse;
use App\Models\Workshop;
use App\Models\WorkshopSession;
use Illuminate\Http\JsonResponse;

class WorkshopSessionController extends Controller
{
    public function index(Workshop $workshop): JsonResponse
    {
        $sessions = $workshop->sessions()->orderBy('session_date')->get();

        return ApiResponse::success(WorkshopSessionResource::collection($sessions));
    }

    public function store(StoreWorkshopSessionRequest $request, Workshop $workshop): JsonResponse
    {
        $session = $workshop->sessions()->create($request->validated());

        return ApiResponse::created(
            WorkshopSessionResource::make($session),
            'Workshop session created successfully.',
        );
    }

    public function show(Workshop $workshop, WorkshopSession $session): JsonResponse
    {
        abort_unless($session->workshop_id === $workshop->id, 404);

        return ApiResponse::success(WorkshopSessionResource::make($session));
    }

    public function update(
        UpdateWorkshopSessionRequest $request,
        Workshop $workshop,
        WorkshopSession $session,
    ): JsonResponse {
        abort_unless($session->workshop_id === $workshop->id, 404);

        $session->update($request->validated());

        return ApiResponse::success(
            WorkshopSessionResource::make($session->fresh()),
            'Workshop session updated successfully.',
        );
    }

    public function destroy(Workshop $workshop, WorkshopSession $session): JsonResponse
    {
        abort_unless($session->workshop_id === $workshop->id, 404);

        $session->delete();

        return ApiResponse::noContent();
    }
}
