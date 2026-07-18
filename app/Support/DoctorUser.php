<?php

namespace App\Support;

use App\Enums\UserType;
use App\Models\DoctorProfile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class DoctorUser
{
    public static function existsRule(): Exists
    {
        return Rule::exists('users', 'id')->where(function ($query) {
            $query->where('type', UserType::Doctor->value)
                ->orWhereIn('id', DoctorProfile::query()->select('user_id'));
        });
    }
}
