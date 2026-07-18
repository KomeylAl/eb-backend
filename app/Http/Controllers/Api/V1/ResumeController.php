<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Resume\StoreResumeRequest;
use App\Http\Resources\ResumeResource;
use App\Http\Responses\ApiResponse;
use App\Models\Resume;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ResumeController extends Controller
{
    public function showSelf(Request $request): JsonResponse
    {
        return $this->show($request->user());
    }

    public function storeSelf(StoreResumeRequest $request): JsonResponse
    {
        return $this->store($request, $request->user());
    }

    public function show(User $doctor): JsonResponse
    {
        abort_unless($doctor->isActingAsDoctor(), 404);

        $resume = Resume::query()->where('doctor_id', $doctor->id)->first();

        if (! $resume) {
            return ApiResponse::success(null, 'Resume not found.');
        }

        return ApiResponse::success(ResumeResource::make($resume));
    }

    public function store(StoreResumeRequest $request, User $doctor): JsonResponse
    {
        abort_unless($doctor->isActingAsDoctor(), 404);

        $data = $request->safe()->except('file');

        $existing = Resume::query()->where('doctor_id', $doctor->id)->first();

        if ($request->hasFile('file')) {
            if ($existing?->file_path) {
                Storage::disk('public')->delete($existing->file_path);
            }
            $data['file_path'] = $request->file('file')->store('doctor_resumes', 'public');
        }

        $resume = Resume::query()->updateOrCreate(
            ['doctor_id' => $doctor->id],
            $data,
        );

        return ApiResponse::success(
            ResumeResource::make($resume),
            'Resume saved successfully.',
        );
    }
}
