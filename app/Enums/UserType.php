<?php

namespace App\Enums;

enum UserType: string
{
    case Admin = 'admin';
    case Doctor = 'doctor';
    case Client = 'client';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Doctor => 'Doctor',
            self::Client => 'Client',
        };
    }
}
