<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Appointment\CreateAppointmentAction;
use App\Actions\Appointment\UpdateAppointmentAction;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Appointment\StoreAppointmentRequest;
use App\Http\Requests\Appointment\UpdateAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Http\Responses\ApiResponse;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Appointment::query()->with(['doctors', 'clients', 'payment']);

        if ($request->user()?->type === UserType::Doctor) {
            $doctorId = $request->user()->id;
            $query->whereHas('doctors', fn ($q) => $q->where('users.id', $doctorId));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('clients', function ($clientQuery) use ($search) {
                    $clientQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                })->orWhereHas('doctors', function ($doctorQuery) use ($search) {
                    $doctorQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->query('date'));
        }

        if ($request->filled('from_date')) {
            $query->whereDate('date', '>=', $request->query('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('date', '<=', $request->query('to_date'));
        }

        if ($request->filled('doctor_id')) {
            $doctorId = $request->query('doctor_id');
            $query->whereHas('doctors', function ($q) use ($doctorId) {
                $q->where('users.id', $doctorId);
            });
        }

        if ($request->filled('client_id')) {
            $clientId = $request->query('client_id');
            $query->whereHas('clients', function ($q) use ($clientId) {
                $q->where('users.id', $clientId);
            });
        }

        if ($request->filled('payment_status')) {
            $paymentStatus = $request->query('payment_status');
            $query->whereHas('payment', function ($q) use ($paymentStatus) {
                $q->where('status', $paymentStatus);
            });
        }

        $sortBy = $request->query('sort_by', 'date');
        $sortDirection = strtolower($request->query('sort_direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['date', 'time', 'amount', 'status', 'created_at'];
        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'date';
        }
        $query->orderBy($sortBy, $sortDirection);

        $perPage = max(1, min((int) $request->query('per_page', 10), 100));
        $appointments = $query->paginate($perPage);

        return ApiResponse::success([
            'items' => AppointmentResource::collection($appointments)->resolve(),
            'meta' => [
                'current_page' => $appointments->currentPage(),
                'last_page' => $appointments->lastPage(),
                'per_page' => $appointments->perPage(),
                'total' => $appointments->total(),
            ],
        ]);
    }

    public function store(StoreAppointmentRequest $request, CreateAppointmentAction $action): JsonResponse
    {
        $appointment = $action->execute($request->validated(), $request->user()?->id);

        return ApiResponse::created(
            AppointmentResource::make($appointment),
            'Appointment created successfully.',
        );
    }

    public function show(Appointment $appointment): JsonResponse
    {
        $appointment->load(['doctors', 'clients', 'payment']);

        return ApiResponse::success(AppointmentResource::make($appointment));
    }

    public function update(
        UpdateAppointmentRequest $request,
        Appointment $appointment,
        UpdateAppointmentAction $action,
    ): JsonResponse {
        $appointment = $action->execute($appointment, $request->validated(), $request->user()?->id);

        return ApiResponse::success(
            AppointmentResource::make($appointment),
            'Appointment updated successfully.',
        );
    }

    public function destroy(Appointment $appointment): JsonResponse
    {
        $appointment->delete();

        return ApiResponse::noContent();
    }
}
