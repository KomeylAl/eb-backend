<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TreatmentProgramStatus;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\TreatmentProgram\StoreTreatmentProgramRequest;
use App\Http\Requests\TreatmentProgram\UpdateTreatmentProgramRequest;
use App\Http\Resources\TreatmentProgramResource;
use App\Http\Responses\ApiResponse;
use App\Models\TreatmentProgram;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TreatmentProgramController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = TreatmentProgram::query()
            ->with(['client', 'doctor'])
            ->withCount('appointments');

        /** @var User|null $user */
        $user = $request->user();
        if ($user?->type === UserType::Doctor) {
            $query->where('doctor_id', $user->id);
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->query('client_id'));
        }

        if ($request->filled('doctor_id') && $user?->type === UserType::Admin) {
            $query->where('doctor_id', $request->query('doctor_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($builder) use ($search) {
                $builder->where('title', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        $programs = $query
            ->orderByDesc('created_at')
            ->paginate(max(1, min((int) $request->query('per_page', 20), 100)));

        return ApiResponse::success([
            'items' => TreatmentProgramResource::collection($programs->items()),
            'meta' => [
                'current_page' => $programs->currentPage(),
                'last_page' => $programs->lastPage(),
                'per_page' => $programs->perPage(),
                'total' => $programs->total(),
            ],
        ]);
    }

    public function store(StoreTreatmentProgramRequest $request): JsonResponse
    {
        $data = $request->validated();
        $program = TreatmentProgram::query()->create([
            'client_id' => $data['client_id'],
            'doctor_id' => $data['doctor_id'],
            'title' => $data['title'] ?? 'برنامه درمان',
            'status' => $data['status'] ?? TreatmentProgramStatus::Active->value,
            'started_at' => $data['started_at'] ?? now()->toDateString(),
            'ended_at' => $data['ended_at'] ?? null,
        ]);

        return ApiResponse::created(
            TreatmentProgramResource::make($program->load(['client', 'doctor'])->loadCount('appointments')),
            'Treatment program created successfully.',
        );
    }

    public function show(TreatmentProgram $treatmentProgram): JsonResponse
    {
        $this->authorizeProgramAccess($treatmentProgram);

        $treatmentProgram->load(['client', 'doctor', 'medicalRecord.images'])->loadCount('appointments');

        return ApiResponse::success(TreatmentProgramResource::make($treatmentProgram));
    }

    public function update(
        UpdateTreatmentProgramRequest $request,
        TreatmentProgram $treatmentProgram,
    ): JsonResponse {
        $this->authorizeProgramAccess($treatmentProgram, adminOnly: true);

        $treatmentProgram->update($request->validated());

        return ApiResponse::success(
            TreatmentProgramResource::make(
                $treatmentProgram->fresh()->load(['client', 'doctor'])->loadCount('appointments')
            ),
            'Treatment program updated successfully.',
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
