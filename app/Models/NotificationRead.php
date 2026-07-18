<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NotificationRead extends Model
{
    use HasUuids;

    protected $fillable = [
        'notification_id',
        'receiver_type',
        'receiver_id',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function notification(): BelongsTo
    {
        return $this->belongsTo(AppNotification::class, 'notification_id');
    }

    public function receiver(): MorphTo
    {
        return $this->morphTo();
    }
}
