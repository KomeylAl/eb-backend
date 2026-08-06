<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Unpaid = 'unpaid';
    case Partial = 'partial';
    case Refunded = 'refunded';
}
