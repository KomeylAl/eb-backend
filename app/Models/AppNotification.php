<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AppNotification extends Model
{
    use HasUuids;

    protected $table = 'app_notifications';

    protected $fillable = [
        'title',
        'message',
        'type',
        'notifiable_type',
        'notifiable_id',
        'priority',
        'delivery_channels',
        'meta',
        'status',
        'scheduled_at',
    ];

    protected function casts(): array
    {
        return [
            'delivery_channels' => 'array',
            'meta' => 'array',
            'scheduled_at' => 'datetime',
        ];
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function reads(): HasMany
    {
        return $this->hasMany(NotificationRead::class, 'notification_id');
    }
}
