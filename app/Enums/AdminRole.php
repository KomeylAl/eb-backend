<?php

namespace App\Enums;

enum AdminRole: string
{
    case Boss = 'boss';
    case Receptionist = 'receptionist';
    case Manager = 'manager';
    case Author = 'author';
    case Accountant = 'accountant';

    public function canManageContent(): bool
    {
        return in_array($this, [self::Author, self::Boss, self::Manager], true);
    }
}
