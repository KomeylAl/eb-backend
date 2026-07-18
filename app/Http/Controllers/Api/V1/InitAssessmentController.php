<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\InitAssessment\StoreInitAssessmentAction;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\InitAssessment\StoreInitAssessmentRequest;
use App\Http\Resources\InitAssessmentResource;
use App\Http\Responses\ApiResponse;
use App\Models\InitAssessment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InitAssessmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = InitAssessment::query()->with(['doctors', 'clients']);

        if ($request->user()?->type === UserType::Doctor) {
            $doctorId = $request->user()->id;
            $query->whereHas('doctors', fn ($q) => $q->where('users.id', $doctorId));
        }

        if ($request->filled('client_id')) {
            $clientId = $request->query('client_id');
            $query->whereHas('clients', fn ($q) => $q->where('users.id', $clientId));
        }

        if ($request->filled('doctor_id')) {
            $doctorId = $request->query('doctor_id');
            $query->whereHas('doctors', fn ($q) => $q->where('users.id', $doctorId));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->whereHas('clients', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->query('sort_by', 'created_at');
        $sortDirection = $request->query('sort_direction', 'desc');
        $allowed = ['created_at', 'date', 'status', 'updated_at'];
        if (! in_array($sortBy, $allowed, true)) {
            $sortBy = 'created_at';
        }

        $assessments = $query
            ->orderBy($sortBy, $sortDirection === 'asc' ? 'asc' : 'desc')
            ->paginate((int) $request->query('per_page', 15));

        return ApiResponse::success([
            'items' => InitAssessmentResource::collection($assessments->items()),
            'meta' => [
                'current_page' => $assessments->currentPage(),
                'last_page' => $assessments->lastPage(),
                'per_page' => $assessments->perPage(),
                'total' => $assessments->total(),
            ],
        ]);
    }

    public function store(
        StoreInitAssessmentRequest $request,
        StoreInitAssessmentAction $action,
    ): JsonResponse {
        $assessment = $action->execute($request->validated());

        return ApiResponse::created(
            InitAssessmentResource::make($assessment),
            'Assessment registered successfully.',
        );
    }

    public function destroy(InitAssessment $initAssessment): JsonResponse
    {
        $initAssessment->delete();

        return ApiResponse::noContent();
    }
}
