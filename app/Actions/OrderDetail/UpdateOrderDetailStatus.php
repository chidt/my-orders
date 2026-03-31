<?php

namespace App\Actions\OrderDetail;

use App\Actions\Order\SyncOrderStatusFromDetails;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateOrderDetailStatus
{
    public function __construct(
        private ManageOrderDetailInventory $inventoryAction,
        private SyncOrderStatusFromDetails $syncOrderStatusAction
    ) {}

    public function execute(Order $order, OrderDetail $detail, int $nextStatusValue, ?string $note = null): void
    {
        $currentStatus = $detail->status instanceof OrderStatus ? $detail->status : OrderStatus::from((int) $detail->status);
        $requestedStatus = OrderStatus::from($nextStatusValue);

        DB::transaction(function () use ($order, $detail, $currentStatus, $requestedStatus, $note): void {
            $nextStatus = $requestedStatus;
            $preOrderInventoryAdjusted = false;
            $reservedInClosingOrderTx = false;

            // Business rule: when choosing "ClosingOrder", auto-advance by stock check.
            if ($requestedStatus === OrderStatus::ClosingOrder) {
                $reserved = $this->inventoryAction->reserveForDetail($detail);
                if ($reserved) {
                    $nextStatus = OrderStatus::Ordered;
                    $reservedInClosingOrderTx = true;
                } else {
                    $this->inventoryAction->createPreOrder($detail);
                    $nextStatus = OrderStatus::PreOrder;
                    $preOrderInventoryAdjusted = true;
                }
            }

            if ($nextStatus === OrderStatus::Ordered
                && ! $reservedInClosingOrderTx
                && $currentStatus !== OrderStatus::Ordered) {
                $reserved = $this->inventoryAction->reserveForDetail($detail);
                if (! $reserved) {
                    $this->inventoryAction->createPreOrder($detail);
                    $nextStatus = OrderStatus::PreOrder;
                    $preOrderInventoryAdjusted = true;
                }
            }

            if ($nextStatus === OrderStatus::PreOrder) {
                if ($currentStatus === OrderStatus::Ordered) {
                    $this->inventoryAction->releaseReserved($detail);
                }
                if (! $preOrderInventoryAdjusted) {
                    $this->inventoryAction->createPreOrder($detail);
                }
            }

            if ($currentStatus === OrderStatus::PreOrder && $nextStatus === OrderStatus::WaitingForStock) {
                $this->inventoryAction->releasePreOrder($detail);
            }

            if ($nextStatus === OrderStatus::Delivering && $currentStatus !== OrderStatus::Delivering) {
                $this->inventoryAction->deductStock($detail);
            }

            if ($nextStatus === OrderStatus::Cancelled) {
                if ($currentStatus->hasStockDeductedForDelivery()) {
                    $this->inventoryAction->returnStock($detail);
                } elseif ($currentStatus->hasActiveStockReservation()) {
                    $this->inventoryAction->releaseReserved($detail);
                }
                $this->inventoryAction->releasePreOrder($detail);
            }

            $detail->update([
                'status' => $nextStatus->value,
                'note' => $note ?: $detail->note,
            ]);

            $this->syncOrderStatusAction->execute($order->fresh());

            Log::info('order_detail_status_updated', [
                'order_id' => $order->id,
                'order_detail_id' => $detail->id,
                'from' => $currentStatus->value,
                'to' => $nextStatus->value,
            ]);
        });
    }
}
