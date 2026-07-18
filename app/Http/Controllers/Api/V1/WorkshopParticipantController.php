<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Workshop\RegisterWorkshopParticipantAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workshop\RegisterParticipantRequest;
use App\Http\Resources\ParticipantResource;
use App\Http\Responses\ApiResponse;
use App\Models\Participant;
use App\Models\Workshop;
use Illuminate\Http\JsonResponse;

class WorkshopParticipantController extends Controller
{
    public function index(Workshop $workshop): JsonResponse
    {
        $participants = $workshop->participants()->orderBy('name')->get();

        return ApiResponse::success(ParticipantResource::collection($participants));
    }

    public function store(
        RegisterParticipantRequest $request,
        Workshop $workshop,
        RegisterWorkshopParticipantAction $action,
    ): JsonResponse {
        $participant = $action->execute($workshop, $request->validated());

        return ApiResponse::created(
            ParticipantResource::make($participant),
            'Participant registered successfully.',
        );
    }

    public function approve(Workshop $workshop, Participant $participant): JsonResponse
    {
        abort_unless($workshop->participants()->where('participants.id', $participant->id)->exists(), 404);

        $workshop->participants()->updateExistingPivot($participant->id, [
            'approved' => true,
            'joined_at' => now(),
        ]);

        $participant = $workshop->participants()->where('participants.id', $participant->id)->first();

        return ApiResponse::success(
            ParticipantResource::make($participant),
            'Participant approved successfully.',
        );
    }

    public function unapprove(Workshop $workshop, Participant $participant): JsonResponse
    {
        abort_unless($workshop->participants()->where('participants.id', $participant->id)->exists(), 404);

        $workshop->participants()->updateExistingPivot($participant->id, [
            'approved' => false,
        ]);

        $participant = $workshop->participants()->where('participants.id', $participant->id)->first();

        return ApiResponse::success(
            ParticipantResource::make($participant),
            'Participant approval revoked.',
        );
    }

    public function destroy(Workshop $workshop, Participant $participant): JsonResponse
    {
        $workshop->participants()->detach($participant->id);

        return ApiResponse::noContent();
    }
}
