<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\ChangePasswordWithOtpAction;
use App\Actions\Auth\RequestPasswordChangeOtpAction;
use App\Enums\AppointmentStatus;
use App\Enums\AssessmentStatus;
use App\Enums\HomeworkStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordWithOtpRequest;
use App\Http\Resources\HomeworkResource;
use App\Http\Resources\InitAssessmentResource;
use App\Http\Resources\PlusAppointmentResource;
use App\Http\Resources\TreatmentProgramResource;
use App\Http\Responses\ApiResponse;
use App\Models\Appointment;
use App\Models\Homework;
use App\Models\InitAssessment;
use App\Models\TreatmentProgram;
use App\Support\PlusIdentity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlusPortalController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        $identity = PlusIdentity::fromRequest($request);
        $client = $identity->client;
        $participant = $identity->participant;

        $appointments = $client
            ? Appointment::query()
                ->whereHas('clients', fn ($q) => $q->where('users.id', $client->id))
                ->get()
            : collect();

        $homeworks = $client
            ? Homework::query()
                ->whereHas('appointment.clients', fn ($q) => $q->where('users.id', $client->id))
                ->get()
            : collect();

        $today = now()->toDateString();

        return ApiResponse::success([
            'today_appointments' => $appointments
                ->filter(fn (Appointment $item) => $item->date?->toDateString() === $today)
                ->count(),
            'upcoming_appointments' => $appointments
                ->filter(fn (Appointment $item) => $item->date?->toDateString() >= $today
                    && $item->status === AppointmentStatus::Pending)
                ->count(),
            'pending_appointments' => $appointments
                ->where('status', AppointmentStatus::Pending)
                ->count(),
            'done_appointments' => $appointments
                ->where('status', AppointmentStatus::Done)
                ->count(),
            'pending_homeworks' => $homeworks
                ->where('status', HomeworkStatus::Assigned)
                ->count(),
            'done_homeworks' => $homeworks
                ->where('status', HomeworkStatus::Done)
                ->count(),
            'treatment_programs' => $client
                ? TreatmentProgram::query()->where('client_id', $client->id)->count()
                : 0,
            'assessments' => $client
                ? InitAssessment::query()
                    ->whereHas('clients', fn ($q) => $q->where('users.id', $client->id))
                    ->count()
                : 0,
            'pending_assessments' => $client
                ? InitAssessment::query()
                    ->whereHas('clients', fn ($q) => $q->where('users.id', $client->id))
                    ->where('status', AssessmentStatus::Pending)
                    ->count()
                : 0,
            'workshops' => $participant
                ? $participant->approvedWorkshops()->count()
                : 0,
            'certificates' => $participant
                ? $participant->certificates()->count()
                : 0,
            'is_client' => $client !== null,
            'is_participant' => $participant !== null,
        ]);
    }

    public function appointments(Request $request): JsonResponse
    {
        $client = PlusIdentity::fromRequest($request)->requireClient();

        $query = Appointment::query()
            ->with(['doctors', 'clients', 'payment', 'treatmentProgram', 'room', 'homeworks'])
            ->whereHas('clients', fn ($q) => $q->where('users.id', $client->id));

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->query('date'));
        }

        $sortBy = $request->query('sort_by', 'date');
        $sortDirection = strtolower((string) $request->query('sort_direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowed = ['date', 'created_at', 'status', 'time'];
        if (! in_array($sortBy, $allowed, true)) {
            $sortBy = 'date';
        }

        $items = $query
            ->orderBy($sortBy, $sortDirection)
            ->orderBy('time', $sortDirection)
            ->paginate($this->perPage($request));

        return $this->paginated(PlusAppointmentResource::collection($items->items()), $items);
    }

    public function showAppointment(Request $request, Appointment $appointment): JsonResponse
    {
        $this->assertClientAppointment($request, $appointment);

        $appointment->load(['doctors', 'clients', 'payment', 'treatmentProgram', 'room', 'homeworks']);

        return ApiResponse::success(PlusAppointmentResource::make($appointment));
    }

    public function assessments(Request $request): JsonResponse
    {
        $client = PlusIdentity::fromRequest($request)->requireClient();

        $query = InitAssessment::query()
            ->with(['doctors', 'clients'])
            ->whereHas('clients', fn ($q) => $q->where('users.id', $client->id));

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $items = $query
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->paginate($this->perPage($request));

        return $this->paginated(InitAssessmentResource::collection($items->items()), $items);
    }

    public function treatmentPrograms(Request $request): JsonResponse
    {
        $client = PlusIdentity::fromRequest($request)->requireClient();

        $query = TreatmentProgram::query()
            ->with(['doctor'])
            ->withCount('appointments')
            ->where('client_id', $client->id);

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $items = $query
            ->orderByDesc('started_at')
            ->orderByDesc('created_at')
            ->paginate($this->perPage($request));

        return $this->paginated(TreatmentProgramResource::collection($items->items()), $items);
    }

    public function showTreatmentProgram(Request $request, TreatmentProgram $treatmentProgram): JsonResponse
    {
        $client = PlusIdentity::fromRequest($request)->requireClient();
        abort_unless($treatmentProgram->client_id === $client->id, 403);

        $treatmentProgram->load([
            'doctor',
            'appointments' => function ($query) {
                $query->with(['homeworks', 'room', 'doctors', 'clients', 'payment'])
                    ->orderByDesc('date')
                    ->orderByDesc('time');
            },
        ])->loadCount('appointments');

        return ApiResponse::success(TreatmentProgramResource::make($treatmentProgram));
    }

    public function homeworks(Request $request): JsonResponse
    {
        $client = PlusIdentity::fromRequest($request)->requireClient();

        $query = Homework::query()
            ->with(['appointment.doctors', 'appointment.clients', 'appointment.treatmentProgram'])
            ->whereHas('appointment.clients', fn ($q) => $q->where('users.id', $client->id));

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $items = $query
            ->orderByDesc('due_at')
            ->orderByDesc('created_at')
            ->paginate($this->perPage($request));

        return $this->paginated(HomeworkResource::collection($items->items()), $items);
    }

    public function completeHomework(Request $request, Homework $homework): JsonResponse
    {
        $client = PlusIdentity::fromRequest($request)->requireClient();
        $homework->loadMissing('appointment.clients');
        abort_unless(
            $homework->appointment?->clients->contains('id', $client->id),
            403,
        );

        $homework->update([
            'status' => HomeworkStatus::Done,
            'completed_at' => now(),
            'completed_by' => $client->id,
        ]);

        return ApiResponse::success(
            HomeworkResource::make($homework->fresh()),
            'تکلیف به‌عنوان انجام‌شده ثبت شد.',
        );
    }

    public function requestPasswordOtp(Request $request, RequestPasswordChangeOtpAction $action): JsonResponse
    {
        $action->execute(PlusIdentity::fromRequest($request)->requireClient());

        return ApiResponse::success(null, 'کد تأیید تغییر رمز ارسال شد.');
    }

    public function changePassword(ChangePasswordWithOtpRequest $request, ChangePasswordWithOtpAction $action): JsonResponse
    {
        $action->execute(
            PlusIdentity::fromRequest($request)->requireClient(),
            $request->validated('code'),
            $request->validated('password'),
        );

        return ApiResponse::success(
            PlusIdentity::fromRequest($request)->toProfileArray(),
            'رمز عبور با موفقیت تغییر کرد.',
        );
    }

    private function assertClientAppointment(Request $request, Appointment $appointment): void
    {
        $client = PlusIdentity::fromRequest($request)->requireClient();
        abort_unless(
            $appointment->clients()->where('users.id', $client->id)->exists(),
            403,
        );
    }

    private function perPage(Request $request): int
    {
        return max(1, min((int) $request->query('per_page', 15), 100));
    }

    private function paginated($collection, $paginator): JsonResponse
    {
        return ApiResponse::success([
            'items' => $collection->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
