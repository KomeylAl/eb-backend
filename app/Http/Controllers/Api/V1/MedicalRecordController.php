<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\MedicalRecord\UpsertMedicalRecordAction;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\MedicalRecord\UpsertMedicalRecordRequest;
use App\Http\Resources\MedicalRecordResource;
use App\Http\Responses\ApiResponse;
use App\Models\MedicalRecord;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class MedicalRecordController extends Controller
{
    public function getClientRecord(string $client): JsonResponse
    {
        $user = User::query()
            ->where('id', $client)
            ->where('type', UserType::Client)
            ->firstOrFail();

        $record = MedicalRecord::query()
            ->with(['client', 'companion', 'doctor', 'supervisor', 'admin', 'images'])
            ->where('client_id', $user->id)
            ->first();

        return ApiResponse::success(
            $record ? MedicalRecordResource::make($record) : null,
        );
    }

    public function store(
        UpsertMedicalRecordRequest $request,
        string $client,
        UpsertMedicalRecordAction $action,
    ): JsonResponse {
        $user = User::query()
            ->where('id', $client)
            ->where('type', UserType::Client)
            ->firstOrFail();

        $record = $action->execute(
            $user,
            $request->validated(),
            $request->file('images', []) ?? [],
        );

        return ApiResponse::created(
            MedicalRecordResource::make($record),
            'Medical record saved successfully.',
        );
    }

    public function update(
        UpsertMedicalRecordRequest $request,
        string $client,
        UpsertMedicalRecordAction $action,
    ): JsonResponse {
        return $this->store($request, $client, $action);
    }
}
