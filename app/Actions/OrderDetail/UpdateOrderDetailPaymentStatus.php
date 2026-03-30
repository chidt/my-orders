<?php

namespace App\Actions\OrderDetail;

use App\Models\OrderDetail;
use Illuminate\Support\Facades\Log;

class UpdateOrderDetailPaymentStatus
{
    /**
     * Payment status progression:
     * 1 Unpaid -> 2 PaymentRequested -> 4 Processing / 5 PendingConfirmation -> 3 Paid
     */
    private const ALLOWED_TRANSITIONS = [
        1 => [2],
        2 => [4, 5, 3],
        4 => [5, 3],
        5 => [4, 3],
        3 => [],
    ];

    public function canTransition(int $from, int $to): bool
    {
        return in_array($to, self::ALLOWED_TRANSITIONS[$from] ?? [], true);
    }

    public function execute(OrderDetail $detail, int $nextPaymentStatus, ?string $note = null): void
    {
        $currentPaymentStatus = (int) $detail->payment_status;

        $detail->update([
            'payment_status' => $nextPaymentStatus,
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
