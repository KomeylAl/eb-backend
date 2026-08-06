<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Finance\BuildFinanceSummaryAction;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    public function summary(Request $request, BuildFinanceSummaryAction $action): JsonResponse
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'doctor_id' => ['nullable', 'uuid', 'exists:users,id'],
        ]);

        return ApiResponse::success($action->execute(
            $data['from'] ?? null,
            $data['to'] ?? null,
            $data['doctor_id'] ?? null,
        ));
    }

    public function byDoctor(Request $request, BuildFinanceSummaryAction $action): JsonResponse
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        return ApiResponse::success($action->byDoctor(
            $data['from'] ?? null,
            $data['to'] ?? null,
        ));
    }

    public function byDay(Request $request, BuildFinanceSummaryAction $action): JsonResponse
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'doctor_id' => ['nullable', 'uuid', 'exists:users,id'],
        ]);

        return ApiResponse::success($action->byDay(
            $data['from'] ?? null,
            $data['to'] ?? null,
            $data['doctor_id'] ?? null,
        ));
    }

    public function compare(Request $request, BuildFinanceSummaryAction $action): JsonResponse
    {
        $data = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'compare_from' => ['required', 'date'],
            'compare_to' => ['required', 'date', 'after_or_equal:compare_from'],
            'doctor_id' => ['nullable', 'uuid', 'exists:users,id'],
        ]);

        return ApiResponse::success($action->compare(
            $data['from'],
            $data['to'],
            $data['compare_from'],
            $data['compare_to'],
            $data['doctor_id'] ?? null,
        ));
    }
}
