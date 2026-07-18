<?php

namespace App\Models;

use App\Enums\AdminRole;
use App\Enums\UserType;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasUuids, Notifiable;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'birth_date',
        'address',
        'type',
        'admin_role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'birth_date' => 'date',
            'password' => 'hashed',
            'type' => UserType::class,
            'admin_role' => AdminRole::class,
        ];
    }

    public function isAdmin(): bool
    {
        return $this->type === UserType::Admin;
    }

    public function isDoctor(): bool
    {
        return $this->type === UserType::Doctor;
    }

    /**
     * True when the user is a doctor account, or an admin who also has a doctor profile.
     */
    public function isActingAsDoctor(): bool
    {
        if ($this->type === UserType::Doctor) {
            return true;
        }

        if ($this->relationLoaded('doctorProfile')) {
            return $this->doctorProfile !== null;
        }

        return $this->doctorProfile()->exists();
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeActingAsDoctors(Builder $query): Builder
    {
        return $query->where(function (Builder $builder) {
            $builder->where('type', UserType::Doctor)
                ->orWhereHas('doctorProfile');
        });
    }

    public function isClient(): bool
    {
        return $this->type === UserType::Client;
    }

    public function isAuthor(): bool
    {
        return $this->isAdmin()
            && $this->admin_role !== null
            && $this->admin_role->canManageContent();
    }

    public function doctorProfile(): HasOne
    {
        return $this->hasOne(DoctorProfile::class);
    }

    public function resume(): HasOne
    {
        return $this->hasOne(Resume::class, 'doctor_id');
    }

    public function doctorResources(): HasMany
    {
        return $this->hasMany(DoctorResource::class, 'doctor_id');
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_doctor', 'doctor_id', 'department_id')
            ->withTimestamps();
    }

    public function medicalRecord(): HasOne
    {
        return $this->hasOne(MedicalRecord::class, 'client_id');
    }

    public function doctorAppointments(): BelongsToMany
    {
        return $this->belongsToMany(Appointment::class, 'appointment_user', 'doctor_id', 'appointment_id')
            ->withPivot('client_id')
            ->withTimestamps();
    }

    public function clientAppointments(): BelongsToMany
    {
        return $this->belongsToMany(Appointment::class, 'appointment_user', 'client_id', 'appointment_id')
            ->withPivot('doctor_id')
            ->withTimestamps();
    }

    public function doctorAssessments(): BelongsToMany
    {
        return $this->belongsToMany(InitAssessment::class, 'assessment_user', 'doctor_id', 'init_assessment_id')
            ->withPivot('client_id')
            ->withTimestamps();
    }

    public function clientAssessments(): BelongsToMany
    {
        return $this->belongsToMany(InitAssessment::class, 'assessment_user', 'client_id', 'init_assessment_id')
            ->withPivot('doctor_id')
            ->withTimestamps();
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'author_id');
    }
}
