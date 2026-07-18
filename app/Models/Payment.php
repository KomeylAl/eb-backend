<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasUuids;

    protected $fillable = [
        'appointment_id',
        'status',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'amount' => 'integer',
        ];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}
