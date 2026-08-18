<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Plus\LoginParticipantAction;
use App\Actions\Plus\RequestPlusLoginOtpAction;
use App\Actions\Plus\VerifyPlusLoginOtpAction;
use App\Enums\ResourceType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Plus\PlusLoginRequest;
use App\Http\Requests\Plus\PlusOtpRequest;
use App\Http\Requests\Plus\PlusVerifyOtpRequest;
use App\Http\Resources\PlusWorkshopMaterialResource;
use App\Http\Resources\PlusWorkshopResource;
use App\Http\Resources\WorkshopCertificateResource;
use App\Http\Responses\ApiResponse;
use App\Models\Participant;
use App\Models\Workshop;
use App\Models\WorkshopCertificate;
use App\Models\WorkshopMaterial;
use App\Support\PlusIdentity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlusController extends Controller
{
    public function login(PlusLoginRequest $request, LoginParticipantAction $action): JsonResponse
    {
        $result = $action->execute(
            $request->validated('phone'),
            $request->secret(),
        );

        return $this->authResponse($result['identity'], $result['token']);
    }

    public function requestLoginOtp(PlusOtpRequest $request, RequestPlusLoginOtpAction $action): JsonResponse
    {
        $action->execute($request->validated('phone'));

        return ApiResponse::success(null, 'کد تأیید ارسال شد.');
    }

    public function verifyLoginOtp(PlusVerifyOtpRequest $request, VerifyPlusLoginOtpAction $action): JsonResponse
    {
        $result = $action->execute(
            $request->validated('phone'),
            $request->validated('code'),
        );

        return $this->authResponse($result['identity'], $result['token']);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return ApiResponse::success(null, 'Logged out successfully.');
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success(
            PlusIdentity::fromRequest($request)->toProfileArray(),
        );
    }

    public function workshops(Request $request): JsonResponse
    {
        $participant = PlusIdentity::fromRequest($request)->participant;

        if (! $participant) {
            return ApiResponse::success([]);
        }

        $workshops = $participant->approvedWorkshops()
            ->withCount('materials')
            ->orderByDesc('participant_workshop.joined_at')
            ->orderByDesc('workshops.start_date')
            ->get()
            ->map(function (Workshop $workshop) use ($participant) {
                $workshop->has_certificate = WorkshopCertificate::query()
                    ->where('workshop_id', $workshop->id)
                    ->where('participant_id', $participant->id)
                    ->exists();

                return $workshop;
            });

        return ApiResponse::success(PlusWorkshopResource::collection($workshops));
    }

    public function showWorkshop(Request $request, Workshop $workshop): JsonResponse
    {
        $participant = $this->approvedParticipant($request, $workshop);

        $workshop->loadCount('materials');
        $workshop->has_certificate = WorkshopCertificate::query()
            ->where('workshop_id', $workshop->id)
            ->where('participant_id', $participant->id)
            ->exists();

        $pivot = $participant->workshops()
            ->where('workshops.id', $workshop->id)
            ->first()?->pivot;

        if ($pivot) {
            $workshop->setRelation('pivot', $pivot);
        }

        return ApiResponse::success(PlusWorkshopResource::make($workshop));
    }

    public function materials(Request $request, Workshop $workshop): JsonResponse
    {
        $this->approvedParticipant($request, $workshop);

        $materials = $workshop->materials()
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->get();

        return ApiResponse::success(PlusWorkshopMaterialResource::collection($materials));
    }

    public function downloadMaterial(
        Request $request,
        Workshop $workshop,
        WorkshopMaterial $material,
    ): StreamedResponse|JsonResponse {
        $this->approvedParticipant($request, $workshop);
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

    public function certificates(Request $request, Workshop $workshop): JsonResponse
    {
        $participant = $this->approvedParticipant($request, $workshop);

        $certificates = WorkshopCertificate::query()
            ->where('workshop_id', $workshop->id)
            ->where('participant_id', $participant->id)
            ->orderByDesc('issued_at')
            ->get();

        return ApiResponse::success(WorkshopCertificateResource::collection($certificates));
    }

    public function downloadCertificate(
        Request $request,
        Workshop $workshop,
        WorkshopCertificate $certificate,
    ): StreamedResponse|JsonResponse {
        $participant = $this->approvedParticipant($request, $workshop);
        abort_unless($certificate->workshop_id === $workshop->id, 404);
        abort_unless($certificate->participant_id === $participant->id, 403);

        if (! $certificate->hasFile()) {
            return ApiResponse::error('This certificate has no uploaded file.', 404);
        }

        $disk = $certificate->disk();
        if (! Storage::disk($disk)->exists($certificate->file_path)) {
            return ApiResponse::error('File not found.', 404);
        }

        return Storage::disk($disk)->download(
            $certificate->file_path,
            $certificate->original_name ?: basename($certificate->file_path),
        );
    }

    public function myCertificates(Request $request): JsonResponse
    {
        $participant = PlusIdentity::fromRequest($request)->participant;

        if (! $participant) {
            return ApiResponse::success([]);
        }

        $certificates = WorkshopCertificate::query()
            ->where('participant_id', $participant->id)
            ->with('workshop')
            ->orderByDesc('issued_at')
            ->get();

        return ApiResponse::success(WorkshopCertificateResource::collection($certificates));
    }

    private function approvedParticipant(Request $request, Workshop $workshop): Participant
    {
        $participant = PlusIdentity::fromRequest($request)->requireParticipant();
        abort_unless($participant->isApprovedFor($workshop), 403, 'Access denied for this workshop.');

        return $participant;
    }

    private function authResponse(PlusIdentity $identity, string $token): JsonResponse
    {
        $profile = $identity->toProfileArray();

        return ApiResponse::success([
            'user' => $profile,
            'participant' => $profile['participant'],
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Logged in successfully.');
    }
}
