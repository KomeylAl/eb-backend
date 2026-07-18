<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassDate extends Model
{
    use HasUuids;

    protected $fillable = [
        'class_id',
        'date',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function courseClass(): BelongsTo
    {
        return $this->belongsTo(CourseClass::class, 'class_id');
    }
}
