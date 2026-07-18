<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Backup\ExportBackupAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\BackupResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class BackupController extends Controller
{
    public function admins(ExportBackupAction $action): JsonResponse
    {
        return $this->export('admins', $action);
    }

    public function doctors(ExportBackupAction $action): JsonResponse
    {
        return $this->export('doctors', $action);
    }

    public function clients(ExportBackupAction $action): JsonResponse
    {
        return $this->export('clients', $action);
    }

    public function resumes(ExportBackupAction $action): JsonResponse
    {
        return $this->export('resumes', $action);
    }

    public function posts(ExportBackupAction $action): JsonResponse
    {
        return $this->export('posts', $action);
    }

    public function categories(ExportBackupAction $action): JsonResponse
    {
        return $this->export('categories', $action);
    }

    public function tags(ExportBackupAction $action): JsonResponse
    {
        return $this->export('tags', $action);
    }

    public function workshops(ExportBackupAction $action): JsonResponse
    {
        return $this->export('workshops', $action);
    }

    public function about(ExportBackupAction $action): JsonResponse
    {
        return $this->export('about', $action);
    }

    private function export(string $type, ExportBackupAction $action): JsonResponse
    {
        try {
            $result = $action->execute($type);
        } catch (InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::success([
            'backup' => BackupResource::make($result['backup']),
            'url' => $result['url'],
        ], 'Backup created successfully.');
    }
}
