<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Doctor\CreateDoctorAction;
use App\Actions\Doctor\ReorderDoctorsAction;
use App\Actions\Doctor\SetDoctorPasswordAction;
use App\Actions\Doctor\UpdateDoctorAction;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\ReorderDoctorsRequest;
use App\Http\Requests\Doctor\SetDoctorPasswordRequest;
use App\Http\Requests\Doctor\StoreDoctorRequest;
use App\Http\Requests\Doctor\UpdateDoctorRequest;
use App\Http\Resources\AppointmentResource;
use App\Http\Resources\DoctorResource;
use App\Http\Responses\ApiResponse;
use App\Models\Appointment;
use App\Models\User;
use App\Services\FileService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    public function __construct(private FileService $files) {}

    public function index(Request $request): JsonResponse
    {
        $query = User::query()
            ->actingAsDoctors()
            ->with(['doctorProfile', 'departments']);

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', "%{$search}%")
                    ->orWhere('users.phone', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->query('sort_by', 'sort_order');
        $defaultDirection = $sortBy === 'sort_order' ? 'asc' : 'desc';
        $sortDirection = strtolower($request->query('sort_direction', $defaultDirection)) === 'asc' ? 'asc' : 'desc';

        if ($sortBy === 'sort_order') {
            $query->leftJoin('doctor_profiles', 'doctor_profiles.user_id', '=', 'users.id')
                ->orderBy('doctor_profiles.sort_order', $sortDirection)
                ->orderBy('users.name')
                ->select('users.*');
        } else {
            $query->orderBy("users.{$sortBy}", $sortDirection);
        }

        $perPage = max(1, min((int) $request->query('per_page', 10), 100));
        $doctors = $query->paginate($perPage);

        return ApiResponse::success([
            'items' => DoctorResource::collection($doctors)->resolve(),
            'meta' => [
                'current_page' => $doctors->currentPage(),
                'last_page' => $doctors->lastPage(),
                'per_page' => $doctors->perPage(),
                'total' => $doctors->total(),
            ],
        ]);
    }

    public function store(StoreDoctorRequest $request, CreateDoctorAction $action): JsonResponse
    {
        $doctor = $action->execute(
            $request->validated(),
            $request->file('avatar'),
        );

        return ApiResponse::created(DoctorResource::make($doctor), 'Doctor created successfully.');
    }

    public function show(User $doctor): JsonResponse
    {
        abort_unless($doctor->isActingAsDoctor(), 404);

        $doctor->load([
            'doctorProfile',
            'departments',
            'resume',
            'doctorResources',
            'receivedComments' => fn ($q) => $q->approved()->orderByDesc('created_at'),
        ])
            ->loadCount(['receivedComments as comments_count' => fn ($q) => $q->approved()])
            ->loadAvg(['receivedComments as rating_avg' => fn ($q) => $q->approved()], 'rating');

        return ApiResponse::success(DoctorResource::make($doctor));
    }

    public function update(UpdateDoctorRequest $request, User $doctor, UpdateDoctorAction $action): JsonResponse
    {
        abort_unless($doctor->isActingAsDoctor(), 404);

        $doctor = $action->execute(
            $doctor,
            $request->validated(),
            $request->file('avatar'),
        );

        return ApiResponse::success(DoctorResource::make($doctor), 'Doctor updated successfully.');
    }

    public function destroy(User $doctor): JsonResponse
    {
        abort_unless($doctor->isActingAsDoctor(), 404);

        $this->files->deleteByPath($doctor->doctorProfile?->avatar);

        $doctor->loadMissing('doctorResources');

        foreach ($doctor->doctorResources as $resource) {
            $this->files->deleteByPath($resource->file_path);
        }

        // Never delete an admin account via the doctors endpoint; only strip doctor data.
        if ($doctor->type === UserType::Admin) {
            foreach ($doctor->doctorResources as $resource) {
                $resource->delete();
            }

            $resume = $doctor->resume;
            $this->files->deleteByPath($resume?->file_path);
            $resume?->delete();

            $doctor->departments()->detach();
            $doctor->doctorProfile?->delete();

            return ApiResponse::noContent();
        }

        $doctor->delete();

        return ApiResponse::noContent();
    }

    public function setPassword(
        SetDoctorPasswordRequest $request,
        User $doctor,
        SetDoctorPasswordAction $action,
    ): JsonResponse {
        abort_unless($doctor->isActingAsDoctor(), 404);

        $doctor = $action->execute($doctor, $request->validated('password'));

        return ApiResponse::success(DoctorResource::make($doctor), 'Password updated successfully.');
    }

    public function reorder(ReorderDoctorsRequest $request, ReorderDoctorsAction $action): JsonResponse
    {
        $orderedIds = $request->validated('ordered_ids');
        $action->execute($orderedIds);

        $doctorsById = User::query()
            ->actingAsDoctors()
            ->whereIn('id', $orderedIds)
            ->with(['doctorProfile', 'departments'])
            ->get()
            ->keyBy('id');

        $doctors = collect($orderedIds)
            ->map(fn (string $id) => $doctorsById->get($id))
            ->filter()
            ->values();

        return ApiResponse::success(
            DoctorResource::collection($doctors),
            'Doctors reordered successfully.',
        );
    }

    public function today(User $doctor): JsonResponse
    {
        return $this->appointmentsForRange($doctor, Carbon::today(), Carbon::today());
    }

    public function yesterday(User $doctor): JsonResponse
    {
        return $this->appointmentsForRange($doctor, Carbon::yesterday(), Carbon::yesterday());
    }

    public function tomorrow(User $doctor): JsonResponse
    {
        return $this->appointmentsForRange($doctor, Carbon::tomorrow(), Carbon::tomorrow());
    }

    public function last7(User $doctor): JsonResponse
    {
        return $this->appointmentsForRange($doctor, Carbon::today()->subDays(7), Carbon::today());
    }

    public function last30(User $doctor): JsonResponse
    {
        return $this->appointmentsForRange($doctor, Carbon::today()->subDays(30), Carbon::today());
    }

    public function next30(User $doctor): JsonResponse
    {
        return $this->appointmentsForRange($doctor, Carbon::today(), Carbon::today()->addDays(30));
    }

    public function all(User $doctor): JsonResponse
    {
        abort_unless($doctor->isActingAsDoctor(), 404);

        $appointments = $this->doctorAppointmentsQuery($doctor)
            ->orderBy('date')
            ->orderBy('time')
            ->get();

        return ApiResponse::success(AppointmentResource::collection($appointments));
    }

    private function appointmentsForRange(User $doctor, Carbon $from, Carbon $to): JsonResponse
    {
        abort_unless($doctor->isActingAsDoctor(), 404);

        $appointments = $this->doctorAppointmentsQuery($doctor)
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->toDateString())
            ->orderBy('date')
            ->orderBy('time')
            ->get();

        return ApiResponse::success(AppointmentResource::collection($appointments));
    }

    private function doctorAppointmentsQuery(User $doctor)
    {
        return Appointment::query()
            ->whereHas('doctors', function ($q) use ($doctor) {
                $q->where('users.id', $doctor->id);
            })
            ->with(['doctors', 'clients', 'payment']);
    }
}
