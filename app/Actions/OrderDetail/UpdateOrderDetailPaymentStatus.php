<?php

namespace App\Actions\OrderDetail;

use App\Enums\PaymentStatus;
use App\Models\OrderDetail;
use Illuminate\Support\Facades\Log;

class UpdateOrderDetailPaymentStatus
{
    /**
     * Payment status progression:
     * 1 Unpaid -> 2 PaymentRequested -> 4 Processing / 5 PendingConfirmation -> 3 Paid
     */
    private const ALLOWED_TRANSITIONS = [
        PaymentStatus::Unpaid->value => [PaymentStatus::PaymentRequested->value],
        PaymentStatus::PaymentRequested->value => [PaymentStatus::Processing->value, PaymentStatus::PendingConfirmation->value, PaymentStatus::Paid->value],
        PaymentStatus::Processing->value => [PaymentStatus::PendingConfirmation->value, PaymentStatus::Paid->value],
        PaymentStatus::PendingConfirmation->value => [PaymentStatus::Processing->value, PaymentStatus::Paid->value],
        PaymentStatus::Cancelled->value => [PaymentStatus::PaymentRequested->value], // Allow re-request after cancellation
        PaymentStatus::Paid->value => [],
    ];

    public function canTransition(int $from, int $to): bool
    {
        return in_array($to, self::ALLOWED_TRANSITIONS[$from] ?? [], true);
    }

    public function execute(OrderDetail $detail, int $nextPaymentStatus, ?string $note = null): void
    {
        $currentPaymentStatus = $detail->payment_status->value; // Access the integer value

        $detail->update([
            'payment_status' => $nextPaymentStatus, // This will be cast to enum by the model
            'note' => $note ?: $detail->note,
        ]);

        Log::info('order_detail_payment_status_updated', [
            'order_id' => $detail->order_id,
            'order_detail_id' => $detail->id,
            'from' => $currentPaymentStatus,
            'to' => $nextPaymentStatus,
        ]);
    }
}
