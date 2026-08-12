<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Room\StoreRoomRequest;
use App\Http\Requests\Room\UpdateRoomRequest;
use App\Http\Resources\AppointmentResource;
use App\Http\Resources\RoomResource;
use App\Http\Responses\ApiResponse;
use App\Models\Appointment;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Room::query()->orderBy('name');

        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $rooms = $query->paginate(max(1, min((int) $request->query('per_page', 50), 100)));

        return ApiResponse::success([
            'items' => RoomResource::collection($rooms->items()),
            'meta' => [
                'current_page' => $rooms->currentPage(),
                'last_page' => $rooms->lastPage(),
                'per_page' => $rooms->perPage(),
                'total' => $rooms->total(),
            ],
        ]);
    }

    public function store(StoreRoomRequest $request): JsonResponse
    {
        $room = Room::query()->create($request->validated());

        return ApiResponse::created(
            RoomResource::make($room),
            'Room created successfully.',
        );
    }

    public function show(Room $room): JsonResponse
    {
        return ApiResponse::success(RoomResource::make($room));
    }

    public function update(UpdateRoomRequest $request, Room $room): JsonResponse
    {
        $room->update($request->validated());

        return ApiResponse::success(
            RoomResource::make($room->fresh()),
            'Room updated successfully.',
        );
    }

    public function destroy(Room $room): JsonResponse
    {
        $room->delete();

        return ApiResponse::noContent();
    }

    public function availability(Request $request): JsonResponse
    {
        $request->validate([
            'date' => ['required', 'date'],
        ]);

        $date = $request->query('date');
        $rooms = Room::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $appointments = Appointment::query()
            ->with(['doctors', 'clients', 'room', 'treatmentProgram'])
            ->whereDate('date', $date)
            ->where('status', AppointmentStatus::Pending->value)
            ->whereNotNull('room_id')
            ->orderBy('time')
            ->get();

        $byRoom = $appointments->groupBy('room_id');

        $payload = $rooms->map(function (Room $room) use ($byRoom) {
            $roomAppointments = $byRoom->get($room->id, collect());

            return [
                'room' => RoomResource::make($room),
                'occupied_slots' => $roomAppointments->map(fn (Appointment $a) => [
                    'time' => $a->time,
                    'appointment_id' => $a->id,
                    'client' => $a->clients->first()?->only(['id', 'name']),
                    'doctor' => $a->doctors->first()?->only(['id', 'name']),
                ])->values(),
            ];
        });

        return ApiResponse::success([
            'date' => $date,
            'rooms' => $payload,
            'appointments' => AppointmentResource::collection($appointments),
        ]);
    }
}
