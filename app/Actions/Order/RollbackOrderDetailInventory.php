<?php

namespace App\Actions\Order;

use App\Actions\OrderDetail\ManageOrderDetailInventory;
use App\Enums\OrderStatus;
use App\Models\OrderDetail;

class RollbackOrderDetailInventory
{
    public function __construct(
        private ManageOrderDetailInventory $inventoryAction
    ) {}

    public function execute(OrderDetail $detail): void
    {
        $status = $detail->status instanceof OrderStatus ? $detail->status : OrderStatus::from((int) $detail->status);

        if ($status->hasStockDeductedForDelivery()) {
            $this->inventoryAction->returnStock($detail);
        } elseif ($status->hasActiveStockReservation()) {
            $this->inventoryAction->releaseReserved($detail);
        }

        if ($status === OrderStatus::PreOrder || $status === OrderStatus::Cancelled) {
            $this->inventoryAction->releasePreOrder($detail);
        }
    }
}
