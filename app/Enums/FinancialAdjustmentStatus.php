<?php

namespace App\Enums;

enum FinancialAdjustmentStatus: string
{
    case Active = 'active';
    case Void = 'void';
}
