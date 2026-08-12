<?php

namespace App\Enums;

enum TreatmentProgramStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Paused = 'paused';
    case Cancelled = 'cancelled';
}
