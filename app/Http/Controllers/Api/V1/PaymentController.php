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

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->whereHas('appointment.clients', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $allowed = ['created_at', 'amount', 'status', 'updated_at'];
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
}
