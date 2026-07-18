<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Restore\ImportRestoreAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Restore\ImportRestoreRequest;
use App\Http\Resources\RestoreResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class RestoreController extends Controller
{
    public function admins(ImportRestoreRequest $request, ImportRestoreAction $action): JsonResponse
    {
        return $this->import('admins', $request, $action);
    }

    public function doctors(ImportRestoreRequest $request, ImportRestoreAction $action): JsonResponse
    {
        return $this->import('doctors', $request, $action);
    }

    public function clients(ImportRestoreRequest $request, ImportRestoreAction $action): JsonResponse
    {
        return $this->import('clients', $request, $action);
    }

    public function resumes(ImportRestoreRequest $request, ImportRestoreAction $action): JsonResponse
    {
        return $this->import('resumes', $request, $action);
    }

    public function posts(ImportRestoreRequest $request, ImportRestoreAction $action): JsonResponse
    {
        return $this->import('posts', $request, $action);
    }

    public function categories(ImportRestoreRequest $request, ImportRestoreAction $action): JsonResponse
    {
        return $this->import('categories', $request, $action);
    }

    public function tags(ImportRestoreRequest $request, ImportRestoreAction $action): JsonResponse
    {
        return $this->import('tags', $request, $action);
    }

    public function workshops(ImportRestoreRequest $request, ImportRestoreAction $action): JsonResponse
    {
        return $this->import('workshops', $request, $action);
    }

    public function about(ImportRestoreRequest $request, ImportRestoreAction $action): JsonResponse
    {
        return $this->import('about', $request, $action);
    }

    private function import(
        string $type,
        ImportRestoreRequest $request,
        ImportRestoreAction $action,
    ): JsonResponse {
        $type = $request->validated('type') ?? $type;

        try {
            $restore = $action->execute($type, $request->validated('data'));
        } catch (InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        } catch (\Throwable $e) {
            report($e);

            return ApiResponse::error(
                'Restore failed: '.$e->getMessage(),
                500,
            );
        }

        return ApiResponse::success(
            RestoreResource::make($restore),
            'Data restored successfully.',
        );
    }
}
