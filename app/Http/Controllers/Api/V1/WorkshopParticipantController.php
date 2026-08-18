<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Workshop\RegisterWorkshopParticipantAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workshop\RegisterParticipantRequest;
use App\Http\Requests\Workshop\UpdateWorkshopParticipantRequest;
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
        $data = $request->validated();
        if ($request->has('name_en') && empty($data['english_name'])) {
            $data['english_name'] = $request->input('name_en');
        }

        $participant = $action->execute($workshop, $data);

        $participant = $workshop->participants()
            ->where('participants.id', $participant->id)
            ->first();

        return ApiResponse::created(
            ParticipantResource::make($participant),
            'Participant registered successfully.',
        );
    }

    public function update(
        UpdateWorkshopParticipantRequest $request,
        Workshop $workshop,
        Participant $participant,
    ): JsonResponse {
        abort_unless(
            $workshop->participants()->where('participants.id', $participant->id)->exists(),
            404,
        );

        $data = $request->safe()->only([
            'name',
            'english_name',
            'phone',
            'national_code',
            'gender',
        ]);

        if ($data !== []) {
            $participant->update($data);
        }

        if ($request->exists('approved')) {
            $approved = $request->boolean('approved');
            $current = $workshop->participants()
                ->where('participants.id', $participant->id)
                ->first();

            $workshop->participants()->updateExistingPivot($participant->id, [
                'approved' => $approved,
                'joined_at' => $approved
                    ? ($current?->pivot?->joined_at ?? now())
                    : null,
            ]);
        }

        $participant = $workshop->participants()
            ->where('participants.id', $participant->id)
            ->first();

        return ApiResponse::success(
            ParticipantResource::make($participant),
            'Participant updated successfully.',
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
