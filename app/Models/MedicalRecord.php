<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicalRecord extends Model
{
    use HasUuids;

    protected $fillable = [
        'client_id',
        'companion_id',
        'doctor_id',
        'supervisor_id',
        'admin_id',
        'treatment_program_id',
        'record_number',
        'reference_source',
        'admission_date',
        'visit_date',
        'chief_complaints',
        'present_illness',
        'past_history',
        'family_history',
        'personal_history',
        'mse',
        'diagnosis',
    ];

    protected function casts(): array
    {
        return [
            'admission_date' => 'date',
            'visit_date' => 'date',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function treatmentProgram(): BelongsTo
    {
        return $this->belongsTo(TreatmentProgram::class);
    }

    public function companion(): BelongsTo
    {
        return $this->belongsTo(Companion::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(RecordImage::class);
    }
}
