<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\About\UpsertAboutRequest;
use App\Http\Resources\AboutResource;
use App\Http\Responses\ApiResponse;
use App\Models\About;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class AboutController extends Controller
{
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
        $data = $request->safe()->except('logo');
        $about = About::query()->first();

        if ($request->hasFile('logo')) {
            if ($about?->logo) {
                Storage::disk('public')->delete($about->logo);
            }
            $data['logo'] = $request->file('logo')->store('about', 'public');
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
