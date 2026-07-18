<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkshopSession extends Model
{
    use HasUuids;

    protected $fillable = [
        'workshop_id',
        'title',
        'description',
        'session_date',
        'start_time',
        'end_time',
        'location',
        'link',
    ];

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
        ];
    }

    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }
}
