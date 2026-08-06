<?php

namespace App\Support;

use App\Enums\PaymentStatus;
use InvalidArgumentException;

class PaymentAmounts
{
    /**
     * @return array{amount: int, paid_amount: int}
     */
    public static function resolve(PaymentStatus $status, int $billAmount, ?int $paidAmount = null): array
    {
        return match ($status) {
            PaymentStatus::Paid => [
                'amount' => $billAmount,
                'paid_amount' => $billAmount,
            ],
            PaymentStatus::Pending, PaymentStatus::Unpaid, PaymentStatus::Refunded => [
                'amount' => $billAmount,
                'paid_amount' => 0,
            ],
            PaymentStatus::Partial => self::resolvePartial($billAmount, $paidAmount),
        };
    }

    /**
     * @return array{amount: int, paid_amount: int}
     */
    private static function resolvePartial(int $billAmount, ?int $paidAmount): array
    {
        if ($paidAmount === null) {
            throw new InvalidArgumentException('paid_amount is required when payment_status is partial.');
        }

        if ($paidAmount <= 0 || $paidAmount >= $billAmount) {
            throw new InvalidArgumentException('paid_amount must be greater than 0 and less than amount for partial payments.');
        }

        return [
            'amount' => $billAmount,
            'paid_amount' => $paidAmount,
        ];
    }
}
