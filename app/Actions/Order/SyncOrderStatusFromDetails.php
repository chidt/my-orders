<?php

namespace App\Actions\Order;

use App\Enums\OrderStatus;
use App\Models\Order;

class SyncOrderStatusFromDetails
{
    public function execute(Order $order): void
    {
        $statuses = $order->orderDetails()
            ->pluck('status')
            ->map(function ($status) {
                if ($status instanceof OrderStatus) {
                    return $status->value;
                }

                return (int) $status;
            })
            ->unique()
            ->values();

        if ($statuses->isEmpty()) {
            $order->update(['status' => OrderStatus::New->value]);

            return;
        }

        if ($statuses->count() === 1) {
            $order->update(['status' => (int) $statuses->first()]);

            return;
        }

        $order->update(['status' => OrderStatus::Processing->value]);
    }
}
