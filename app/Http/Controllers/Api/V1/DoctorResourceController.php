<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ResourceType;
use App\Http\Controllers\Controller;
use App\Http\Requests\DoctorResource\StoreDoctorResourceRequest;
use App\Http\Requests\DoctorResource\UpdateDoctorResourceRequest;
use App\Http\Resources\DoctorResourceItemResource;
use App\Http\Responses\ApiResponse;
use App\Models\DoctorResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DoctorResourceController extends Controller
{
    private const ALLOWED_SORT_COLUMNS = ['created_at', 'updated_at', 'title', 'type'];

    public function indexSelf(Request $request): JsonResponse
    {
        return $this->index($request, $request->user());
    }

    public function storeSelf(StoreDoctorResourceRequest $request): JsonResponse
    {
        return $this->store($request, $request->user());
    }

    public function updateSelf(
        UpdateDoctorResourceRequest $request,
        DoctorResource $doctorResource,
    ): JsonResponse {
        return $this->update($request, $request->user(), $doctorResource);
    }

    public function destroySelf(Request $request, DoctorResource $doctorResource): JsonResponse
    {
        return $this->destroy($request->user(), $doctorResource);
    }

    public function index(Request $request, User $doctor): JsonResponse
    {
        abort_unless($doctor->isActingAsDoctor(), 404);

        $query = DoctorResource::query()->where('doctor_id', $doctor->id);

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $type = $request->query('type');
            if (in_array($type, [ResourceType::Link->value, ResourceType::File->value], true)) {
                $query->where('type', $type);
            }
        }

        $sortBy = $request->query('sort_by', 'created_at');
        if (! in_array($sortBy, self::ALLOWED_SORT_COLUMNS, true)) {
            $sortBy = 'created_at';
        }

        $sortDirection = strtolower($request->query('sort_direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDirection);

        $perPage = max(1, min((int) $request->query('per_page', 10), 100));
        $resources = $query->paginate($perPage);

        return ApiResponse::success([
            'items' => DoctorResourceItemResource::collection($resources)->resolve(),
            'meta' => [
                'current_page' => $resources->currentPage(),
                'last_page' => $resources->lastPage(),
                'per_page' => $resources->perPage(),
                'total' => $resources->total(),
            ],
        ]);
    }

    public function store(StoreDoctorResourceRequest $request, User $doctor): JsonResponse
    {
        abort_unless($doctor->isActingAsDoctor(), 404);

        $type = ResourceType::from($request->validated('type'));
        $data = [
            'doctor_id' => $doctor->id,
            'title' => $request->validated('title'),
            'type' => $type,
            'description' => $request->validated('description'),
            'link' => null,
            'file_path' => null,
        ];

        if ($type === ResourceType::Link) {
            $data['link'] = $request->validated('link');
        }

        if ($type === ResourceType::File && $request->hasFile('file')) {
            $data['file_path'] = $request->file('file')->store('doctor_resources', 'public');
        }

        $resource = DoctorResource::query()->create($data);

        return ApiResponse::created(
            DoctorResourceItemResource::make($resource),
            'Doctor resource created successfully.',
        );
    }

    public function update(
        UpdateDoctorResourceRequest $request,
        User $doctor,
        DoctorResource $doctorResource,
    ): JsonResponse {
        abort_unless($doctor->isActingAsDoctor(), 404);
        abort_unless($doctorResource->doctor_id === $doctor->id, 404);

        $validated = $request->validated();
        $type = isset($validated['type'])
            ? ResourceType::from($validated['type'])
            : $doctorResource->type;

        $data = $request->safe()->only(['title', 'description']);
        $data['type'] = $type;

        if ($type === ResourceType::Link) {
            if ($request->filled('link')) {
                $data['link'] = $validated['link'];
            }

            if ($doctorResource->file_path) {
                Storage::disk('public')->delete($doctorResource->file_path);
            }
            $data['file_path'] = null;
        }

        if ($type === ResourceType::File) {
            $data['link'] = null;

            if ($request->hasFile('file')) {
                if ($doctorResource->file_path) {
                    Storage::disk('public')->delete($doctorResource->file_path);
                }
                $data['file_path'] = $request->file('file')->store('doctor_resources', 'public');
            }
        }

        $doctorResource->update($data);

        return ApiResponse::success(
            DoctorResourceItemResource::make($doctorResource->refresh()),
            'Doctor resource updated successfully.',
        );
    }

    public function destroy(User $doctor, DoctorResource $doctorResource): JsonResponse
    {
        abort_unless($doctor->isActingAsDoctor(), 404);
        abort_unless($doctorResource->doctor_id === $doctor->id, 404);

        if ($doctorResource->file_path) {
            Storage::disk('public')->delete($doctorResource->file_path);
        }

        $doctorResource->delete();

        return ApiResponse::noContent();
    }
}
