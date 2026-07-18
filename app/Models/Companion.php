<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Companion extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'phone',
        'address',
        'birth_date',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    public function medicalRecords(): HasMany
    {
        return $this->hasMany(MedicalRecord::class);
    }
}
