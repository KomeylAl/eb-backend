<?php

namespace App\Enums;

enum FinancialAdjustmentType: string
{
    case Discount = 'discount';
    case Credit = 'credit';
    case Debit = 'debit';
}
