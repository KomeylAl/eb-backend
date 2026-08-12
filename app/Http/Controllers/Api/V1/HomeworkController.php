<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\HomeworkStatus;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Homework\StoreHomeworkRequest;
use App\Http\Requests\Homework\UpdateHomeworkRequest;
use App\Http\Resources\HomeworkResource;
use App\Http\Responses\ApiResponse;
use App\Models\Appointment;
use App\Models\Homework;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class HomeworkController extends Controller
{
    public function index(Appointment $appointment): JsonResponse
    {
        $this->authorizeAppointmentAccess($appointment);

        $items = $appointment->homeworks()->orderByDesc('created_at')->get();

        return ApiResponse::success(HomeworkResource::collection($items));
    }

    public function store(StoreHomeworkRequest $request, Appointment $appointment): JsonResponse
    {
        $this->authorizeAppointmentAccess($appointment);

        $data = $request->validated();
        $homework = Homework::query()->create([
            'appointment_id' => $appointment->id,
            'type' => $data['type'],
            'title' => $data['title'],
            'body' => $data['body'] ?? null,
            'meta' => $data['meta'] ?? null,
            'status' => $data['status'] ?? HomeworkStatus::Assigned->value,
            'due_at' => $data['due_at'] ?? null,
            'created_by' => $request->user()?->id,
        ]);

        return ApiResponse::created(
            HomeworkResource::make($homework),
            'Homework created successfully.',
        );
    }

    public function update(
        UpdateHomeworkRequest $request,
        Homework $homework,
    ): JsonResponse {
        $homework->loadMissing('appointment');
        $this->authorizeAppointmentAccess($homework->appointment);

        $data = $request->validated();

        if (($data['status'] ?? null) === HomeworkStatus::Done->value) {
            $data['completed_at'] = $data['completed_at'] ?? now();
            $data['completed_by'] = $request->user()?->id;
        }

        if (($data['status'] ?? null) === HomeworkStatus::Assigned->value) {
            $data['completed_at'] = null;
            $data['completed_by'] = null;
        }

        $homework->update($data);

        return ApiResponse::success(
            HomeworkResource::make($homework->fresh()),
            'Homework updated successfully.',
        );
    }

    public function destroy(Homework $homework): JsonResponse
    {
        $homework->loadMissing('appointment');
        $this->authorizeAppointmentAccess($homework->appointment);
        $homework->delete();

        return ApiResponse::noContent();
    }

    private function authorizeAppointmentAccess(?Appointment $appointment): void
    {
        if (! $appointment) {
            abort(404);
        }

        /** @var User $user */
        $user = request()->user();

        if ($user->type === UserType::Admin) {
            return;
        }

        if ($user->type !== UserType::Doctor) {
            abort(403, 'Forbidden.');
        }

        $isDoctorOnPivot = $appointment->doctors()->where('users.id', $user->id)->exists();
        $isProgramDoctor = $appointment->treatmentProgram()
            ->where('doctor_id', $user->id)
            ->exists();

        if (! $isDoctorOnPivot && ! $isProgramDoctor) {
            abort(403, 'Forbidden.');
        }
    }
}
