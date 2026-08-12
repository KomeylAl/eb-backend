<?php

namespace App\Models;

use App\Enums\HomeworkStatus;
use App\Enums\HomeworkType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Homework extends Model
{
    use HasUuids;

    protected $table = 'homeworks';

    protected $fillable = [
        'appointment_id',
        'type',
        'title',
        'body',
        'meta',
        'status',
        'due_at',
        'completed_at',
        'completed_by',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => HomeworkType::class,
            'status' => HomeworkStatus::class,
            'meta' => 'array',
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
