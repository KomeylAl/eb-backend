<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Http\Responses\ApiResponse;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Payment::query()->with(['appointment.doctors', 'appointment.clients']);

        if ($request->filled('client_id')) {
            $clientId = $request->query('client_id');
            $query->whereHas('appointment.clients', fn ($q) => $q->where('users.id', $clientId));
        }

        if ($request->filled('doctor_id')) {
            $doctorId = $request->query('doctor_id');
            $query->whereHas('appointment.doctors', fn ($q) => $q->where('users.id', $doctorId));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('method')) {
            $query->where('method', $request->query('method'));
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->query('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->query('to_date'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->whereHas('appointment.clients', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $allowed = ['created_at', 'amount', 'paid_amount', 'status', 'updated_at'];
        $sortBy = $request->query('sort_by', 'created_at');
        if (! in_array($sortBy, $allowed, true)) {
            $sortBy = 'created_at';
        }

        $payments = $query
            ->orderBy($sortBy, $request->query('sort_direction', 'desc') === 'asc' ? 'asc' : 'desc')
            ->paginate(min(100, max(1, (int) $request->query('per_page', 15))));

        return ApiResponse::success([
            'items' => PaymentResource::collection($payments->items()),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
            ],
        ]);
    }

    public function show(Payment $payment): JsonResponse
    {
        $payment->load(['appointment.doctors', 'appointment.clients']);

        return ApiResponse::success(PaymentResource::make($payment));
    }
}
