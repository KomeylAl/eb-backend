<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\About\UpsertAboutRequest;
use App\Http\Resources\AboutResource;
use App\Http\Responses\ApiResponse;
use App\Models\About;
use App\Services\FileService;
use Illuminate\Http\JsonResponse;

class AboutController extends Controller
{
    public function __construct(private FileService $files) {}

    public function index(): JsonResponse
    {
        $about = About::query()->first();

        if (! $about) {
            return ApiResponse::error('About information not found.', 404);
        }

        return ApiResponse::success(AboutResource::make($about));
    }

    public function upsert(UpsertAboutRequest $request): JsonResponse
    {
        $data = $request->safe()->except(['logo', 'logo_media_id']);
        $about = About::query()->first();

        $path = $this->files->assign(
            'about',
            $request->file('logo'),
            $request->validated('logo_media_id'),
            uploadedBy: $request->user()?->id,
        );

        if ($path) {
            $data['logo'] = $path;
        }

        if ($about) {
            $about->update($data);
        } else {
            $about = About::query()->create($data);
        }

        return ApiResponse::success(
            AboutResource::make($about->fresh()),
            'About information saved successfully.',
        );
    }
}
