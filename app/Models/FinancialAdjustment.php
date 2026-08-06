<?php

namespace App\Models;

use App\Enums\FinancialAdjustmentStatus;
use App\Enums\FinancialAdjustmentType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialAdjustment extends Model
{
    use HasUuids;

    protected $fillable = [
        'client_id',
        'admin_id',
        'appointment_id',
        'invoice_id',
        'type',
        'amount',
        'reason',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'type' => FinancialAdjustmentType::class,
            'status' => FinancialAdjustmentStatus::class,
            'amount' => 'integer',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
