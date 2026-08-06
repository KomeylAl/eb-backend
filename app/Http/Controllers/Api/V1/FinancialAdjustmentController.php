<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\FinancialAdjustmentStatus;
use App\Enums\FinancialAdjustmentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\FinancialAdjustment\StoreFinancialAdjustmentRequest;
use App\Http\Requests\FinancialAdjustment\UpdateFinancialAdjustmentRequest;
use App\Http\Resources\FinancialAdjustmentResource;
use App\Http\Responses\ApiResponse;
use App\Models\FinancialAdjustment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinancialAdjustmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = FinancialAdjustment::query()->with(['client', 'admin', 'appointment', 'invoice']);

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->query('client_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('appointment_id')) {
            $query->where('appointment_id', $request->query('appointment_id'));
        }

        if ($request->filled('invoice_id')) {
            $query->where('invoice_id', $request->query('invoice_id'));
        }

        $adjustments = $query
            ->orderByDesc('created_at')
            ->paginate(min(100, max(1, (int) $request->query('per_page', 15))));

        return ApiResponse::success([
            'items' => FinancialAdjustmentResource::collection($adjustments->items()),
            'meta' => [
                'current_page' => $adjustments->currentPage(),
                'last_page' => $adjustments->lastPage(),
                'per_page' => $adjustments->perPage(),
                'total' => $adjustments->total(),
            ],
        ]);
    }

    public function store(StoreFinancialAdjustmentRequest $request): JsonResponse
    {
        $data = $request->validated();

        $adjustment = FinancialAdjustment::query()->create([
            ...$data,
            'admin_id' => $request->user()->id,
            'type' => FinancialAdjustmentType::from($data['type']),
            'status' => FinancialAdjustmentStatus::from($data['status'] ?? FinancialAdjustmentStatus::Active->value),
        ])->load(['client', 'admin', 'appointment', 'invoice']);

        return ApiResponse::created(
            FinancialAdjustmentResource::make($adjustment),
            'Financial adjustment created successfully.',
        );
    }

    public function show(FinancialAdjustment $financialAdjustment): JsonResponse
    {
        $financialAdjustment->load(['client', 'admin', 'appointment', 'invoice']);

        return ApiResponse::success(FinancialAdjustmentResource::make($financialAdjustment));
    }

    public function update(
        UpdateFinancialAdjustmentRequest $request,
        FinancialAdjustment $financialAdjustment,
    ): JsonResponse {
        $data = $request->validated();

        if (isset($data['type'])) {
            $data['type'] = FinancialAdjustmentType::from($data['type']);
        }
        if (isset($data['status'])) {
            $data['status'] = FinancialAdjustmentStatus::from($data['status']);
        }

        $financialAdjustment->update($data);
        $financialAdjustment->load(['client', 'admin', 'appointment', 'invoice']);

        return ApiResponse::success(
            FinancialAdjustmentResource::make($financialAdjustment),
            'Financial adjustment updated successfully.',
        );
    }

    public function destroy(FinancialAdjustment $financialAdjustment): JsonResponse
    {
        $financialAdjustment->delete();

        return ApiResponse::noContent();
    }
}
