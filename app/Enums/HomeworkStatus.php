<?php

namespace App\Enums;

enum HomeworkStatus: string
{
    case Assigned = 'assigned';
    case Done = 'done';
    case Cancelled = 'cancelled';
}
