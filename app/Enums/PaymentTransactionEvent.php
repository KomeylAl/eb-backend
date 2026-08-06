<?php

namespace App\Enums;

enum PaymentTransactionEvent: string
{
    case Created = 'created';
    case StatusChanged = 'status_changed';
    case AmountChanged = 'amount_changed';
    case MethodChanged = 'method_changed';
}
