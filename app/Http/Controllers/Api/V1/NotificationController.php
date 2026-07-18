<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\StoreNotificationRequest;
use App\Http\Resources\AppNotificationResource;
use App\Http\Responses\ApiResponse;
use App\Models\AppNotification;
use App\Models\NotificationRead;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = $this->scopedQuery($user);

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->query('priority'));
        }

        $notifications = $query
            ->orderBy($request->query('sort_by', 'created_at'), $request->query('sort_direction', 'desc'))
            ->paginate((int) $request->query('per_page', 20));

        return ApiResponse::success([
            'items' => AppNotificationResource::collection($notifications->items()),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    public function store(StoreNotificationRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['type'] = $data['type'] ?? 'system';
        $data['priority'] = $data['priority'] ?? 'normal';
        $data['status'] = $data['status'] ?? 'active';

        if (empty($data['delivery_channels'])) {
            $data['delivery_channels'] = match ($data['priority']) {
                'high' => ['in_app', 'email', 'sms'],
                'medium' => ['in_app', 'email'],
                default => ['in_app'],
            };
        }

        $notification = AppNotification::query()->create($data);

        return ApiResponse::created(
            AppNotificationResource::make($notification),
            'Notification created successfully.',
        );
    }

    public function markAsRead(Request $request, AppNotification $notification): JsonResponse
    {
        $user = $request->user();

        NotificationRead::query()->updateOrCreate(
            [
                'notification_id' => $notification->id,
                'receiver_type' => User::class,
                'receiver_id' => $user->id,
            ],
            [
                'read_at' => now(),
            ],
        );

        return ApiResponse::success(null, 'Notification marked as read.');
    }

    public function unread(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = $this->scopedQuery($user)
            ->whereDoesntHave('reads', function ($q) use ($user) {
                $q->where('receiver_type', User::class)
                    ->where('receiver_id', $user->id);
            })
            ->orderByDesc('created_at')
            ->paginate((int) $request->query('per_page', 20));

        return ApiResponse::success([
            'items' => AppNotificationResource::collection($notifications->items()),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    private function scopedQuery(User $user)
    {
        return AppNotification::query()->where(function ($q) use ($user) {
            $q->where(function ($sub) use ($user) {
                $sub->where('notifiable_type', User::class)
                    ->where('notifiable_id', $user->id);
            })
                ->orWhere(function ($sub) use ($user) {
                    $sub->where('notifiable_type', User::class)
                        ->whereNull('notifiable_id')
                        ->where('meta->user_type', $user->type?->value);
                })
                ->orWhereNull('notifiable_type');
        });
    }
}
