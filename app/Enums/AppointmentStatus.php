<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Pending = 'pending';
    case Done = 'done';
}
