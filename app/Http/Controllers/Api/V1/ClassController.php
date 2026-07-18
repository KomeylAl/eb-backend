<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CourseClass\UpsertCourseClassAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CourseClass\StoreCourseClassRequest;
use App\Http\Requests\CourseClass\UpdateCourseClassRequest;
use App\Http\Resources\CourseClassResource;
use App\Http\Responses\ApiResponse;
use App\Models\CourseClass;
use Illuminate\Http\JsonResponse;

class ClassController extends Controller
{
    public function index(): JsonResponse
    {
        $classes = CourseClass::query()
            ->with(['dates', 'users'])
            ->orderByDesc('created_at')
            ->get();

        return ApiResponse::success(CourseClassResource::collection($classes));
    }

    public function store(StoreCourseClassRequest $request, UpsertCourseClassAction $action): JsonResponse
    {
        $courseClass = $action->execute($request->validated());

        return ApiResponse::created(
            CourseClassResource::make($courseClass),
            'Class created successfully.',
        );
    }

    public function show(CourseClass $class): JsonResponse
    {
        $class->load(['dates', 'users']);

        return ApiResponse::success(CourseClassResource::make($class));
    }

    public function update(
        UpdateCourseClassRequest $request,
        CourseClass $class,
        UpsertCourseClassAction $action,
    ): JsonResponse {
        $courseClass = $action->execute($request->validated(), $class);

        return ApiResponse::success(
            CourseClassResource::make($courseClass),
            'Class updated successfully.',
        );
    }

    public function destroy(CourseClass $class): JsonResponse
    {
        $class->delete();

        return ApiResponse::noContent();
    }
}
