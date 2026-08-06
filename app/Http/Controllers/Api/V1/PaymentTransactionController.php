<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentTransactionResource;
use App\Http\Responses\ApiResponse;
use App\Models\PaymentTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentTransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = PaymentTransaction::query()
            ->with(['actor', 'payment.appointment.clients', 'payment.appointment.doctors']);

        if ($request->filled('payment_id')) {
            $query->where('payment_id', $request->query('payment_id'));
        }

        if ($request->filled('client_id')) {
            $clientId = $request->query('client_id');
            $query->whereHas('payment.appointment.clients', fn ($q) => $q->where('users.id', $clientId));
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->query('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->query('to_date'));
        }

        $transactions = $query
            ->orderByDesc('created_at')
            ->paginate(min(100, max(1, (int) $request->query('per_page', 20))));

        return ApiResponse::success([
            'items' => PaymentTransactionResource::collection($transactions->items()),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }
}
