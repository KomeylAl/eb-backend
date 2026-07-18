<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Appointment extends Model
{
    use HasUuids;

    protected $fillable = [
        'date',
        'time',
        'amount',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'integer',
            'status' => AppointmentStatus::class,
        ];
    }

    public function doctors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'appointment_user', 'appointment_id', 'doctor_id')
            ->withPivot('client_id')
            ->withTimestamps();
    }

    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'appointment_user', 'appointment_id', 'client_id')
            ->withPivot('doctor_id')
            ->withTimestamps();
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function doctor(): ?User
    {
        return $this->doctors->first();
    }

    public function client(): ?User
    {
        return $this->clients->first();
    }
}
