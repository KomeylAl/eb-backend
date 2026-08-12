<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\MedicalRecord\UpsertMedicalRecordAction;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\MedicalRecord\DoctorUpdateMedicalRecordRequest;
use App\Http\Requests\MedicalRecord\UpsertMedicalRecordRequest;
use App\Http\Resources\MedicalRecordResource;
use App\Http\Resources\TreatmentProgramResource;
use App\Http\Responses\ApiResponse;
use App\Models\MedicalRecord;
use App\Models\TreatmentProgram;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class MedicalRecordController extends Controller
{
    public function showForProgram(TreatmentProgram $treatmentProgram): JsonResponse
    {
        $this->authorizeProgramAccess($treatmentProgram);

        $record = MedicalRecord::query()
            ->with(['treatmentProgram', 'client', 'companion', 'doctor', 'supervisor', 'admin', 'images'])
            ->where('treatment_program_id', $treatmentProgram->id)
            ->first();

        return ApiResponse::success([
            'program' => TreatmentProgramResource::make($treatmentProgram->load(['client', 'doctor'])),
            'record' => $record ? MedicalRecordResource::make($record) : null,
        ]);
    }

    public function upsertForProgram(
        UpsertMedicalRecordRequest $request,
        TreatmentProgram $treatmentProgram,
        UpsertMedicalRecordAction $action,
    ): JsonResponse {
        $this->authorizeProgramAccess($treatmentProgram, adminOnly: true);

        $record = $action->execute(
            $treatmentProgram,
            $request->validated(),
            $request->file('images', []) ?? [],
        );

        return ApiResponse::created(
            MedicalRecordResource::make($record),
            'Medical record saved successfully.',
        );
    }

    public function updateClinicalForProgram(
        DoctorUpdateMedicalRecordRequest $request,
        TreatmentProgram $treatmentProgram,
        UpsertMedicalRecordAction $action,
    ): JsonResponse {
        $this->authorizeProgramAccess($treatmentProgram);

        /** @var User $doctor */
        $doctor = $request->user();

        $record = $action->executeClinical(
            $treatmentProgram,
            $doctor,
            $request->validated(),
            $request->file('images', []) ?? [],
        );

        return ApiResponse::success(
            MedicalRecordResource::make($record),
            'Medical record saved successfully.',
        );
    }

    private function authorizeProgramAccess(TreatmentProgram $program, bool $adminOnly = false): void
    {
        /** @var User $user */
        $user = request()->user();

        if ($user->type === UserType::Admin) {
            return;
        }

        if ($adminOnly || $user->type !== UserType::Doctor || $program->doctor_id !== $user->id) {
            abort(403, 'Forbidden.');
        }
    }
}
