<?php

namespace App\Models;

use App\Enums\PaymentTransactionEvent;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    use HasUuids;

    protected $fillable = [
        'payment_id',
        'actor_id',
        'event',
        'old_status',
        'new_status',
        'old_paid_amount',
        'new_paid_amount',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'event' => PaymentTransactionEvent::class,
            'old_paid_amount' => 'integer',
            'new_paid_amount' => 'integer',
            'meta' => 'array',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
