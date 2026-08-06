<?php

namespace App\Actions\Payment;

use App\Enums\PaymentTransactionEvent;
use App\Models\Payment;
use App\Models\PaymentTransaction;

class LogPaymentTransactionAction
{
    /**
     * @param  array<string, mixed>|null  $meta
     */
    public function execute(
        Payment $payment,
        PaymentTransactionEvent $event,
        ?string $actorId = null,
        ?string $oldStatus = null,
        ?string $newStatus = null,
        ?int $oldPaidAmount = null,
        ?int $newPaidAmount = null,
        ?array $meta = null,
    ): PaymentTransaction {
        return PaymentTransaction::query()->create([
            'payment_id' => $payment->id,
            'actor_id' => $actorId,
            'event' => $event,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'old_paid_amount' => $oldPaidAmount,
            'new_paid_amount' => $newPaidAmount,
            'meta' => $meta,
        ]);
    }
}
