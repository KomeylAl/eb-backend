<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Hero\ReorderHeroSlidesAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hero\ReorderHeroSlidesRequest;
use App\Http\Requests\Hero\StoreHeroSlideRequest;
use App\Http\Requests\Hero\UpdateHeroSlideRequest;
use App\Http\Requests\Hero\UpsertHeroSettingRequest;
use App\Http\Resources\HeroSettingResource;
use App\Http\Resources\HeroSlideResource;
use App\Http\Responses\ApiResponse;
use App\Models\HeroSetting;
use App\Models\HeroSlide;
use App\Services\FileService;
use Illuminate\Http\JsonResponse;

class HeroController extends Controller
{
    public function __construct(private FileService $files) {}

    public function index(): JsonResponse
    {
        $settings = HeroSetting::query()->first();
        $slides = HeroSlide::query()->active()->ordered()->get();

        return ApiResponse::success([
            'settings' => $settings
                ? (new HeroSettingResource($settings))->resolve()
                : [
                    'id' => null,
                    'background' => null,
                    'background_url' => null,
                    'autoplay_ms' => 5000,
                ],
            'slides' => HeroSlideResource::collection($slides)->resolve(),
        ]);
    }

    public function upsertSettings(UpsertHeroSettingRequest $request): JsonResponse
    {
        $data = $request->safe()->except(['background', 'background_media_id']);
        $settings = HeroSetting::query()->first();

        $path = $this->files->assign(
            'hero',
            $request->file('background'),
            $request->validated('background_media_id'),
            uploadedBy: $request->user()?->id,
        );

        if ($path) {
            $data['background'] = $path;
        }

        if (! array_key_exists('autoplay_ms', $data) || $data['autoplay_ms'] === null) {
            unset($data['autoplay_ms']);
        }

        if ($settings) {
            $settings->update($data);
        } else {
            $settings = HeroSetting::query()->create(array_merge([
                'autoplay_ms' => 5000,
            ], $data));
        }

        return ApiResponse::success(
            HeroSettingResource::make($settings->fresh()),
            'Hero settings saved successfully.',
        );
    }

    public function slides(): JsonResponse
    {
        $slides = HeroSlide::query()->ordered()->get();

        return ApiResponse::success(HeroSlideResource::collection($slides));
    }

    public function storeSlide(StoreHeroSlideRequest $request): JsonResponse
    {
        $data = $request->safe()->except(['image', 'image_media_id']);

        $path = $this->files->assign(
            'hero',
            $request->file('image'),
            $request->validated('image_media_id'),
            uploadedBy: $request->user()?->id,
        );

        if ($path) {
            $data['image'] = $path;
        }

        if (! isset($data['sort_order'])) {
            $data['sort_order'] = (int) HeroSlide::query()->max('sort_order') + 1;
        }

        if (! array_key_exists('is_active', $data) || $data['is_active'] === null) {
            $data['is_active'] = true;
        }

        $slide = HeroSlide::query()->create($data);

        return ApiResponse::created(
            HeroSlideResource::make($slide),
            'Hero slide created successfully.',
        );
    }

    public function showSlide(HeroSlide $hero_slide): JsonResponse
    {
        return ApiResponse::success(HeroSlideResource::make($hero_slide));
    }

    public function updateSlide(UpdateHeroSlideRequest $request, HeroSlide $hero_slide): JsonResponse
    {
        $data = $request->safe()->except(['image', 'image_media_id']);

        $path = $this->files->assign(
            'hero',
            $request->file('image'),
            $request->validated('image_media_id'),
            uploadedBy: $request->user()?->id,
        );

        if ($path) {
            $data['image'] = $path;
        }

        $hero_slide->update($data);

        return ApiResponse::success(
            HeroSlideResource::make($hero_slide->fresh()),
            'Hero slide updated successfully.',
        );
    }

    public function destroySlide(HeroSlide $hero_slide): JsonResponse
    {
        $hero_slide->delete();

        return ApiResponse::success(null, 'Hero slide deleted successfully.');
    }

    public function reorderSlides(ReorderHeroSlidesRequest $request, ReorderHeroSlidesAction $action): JsonResponse
    {
        $action->execute($request->validated('ordered_ids'));

        $slides = HeroSlide::query()->ordered()->get();

        return ApiResponse::success(
            HeroSlideResource::collection($slides),
            'Hero slides reordered successfully.',
        );
    }
}
