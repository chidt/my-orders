<?php

namespace App\Actions\Order;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class DeleteOrder
{
    public function __construct(
        private RollbackOrderDetailInventory $rollbackInventoryAction
    ) {}

    public function execute(Order $order): void
    {
        $status = $order->status instanceof OrderStatus ? $order->status : OrderStatus::from((int) $order->status);
        if ($status->isFinal()) {
            throw new \DomainException('Đơn hàng đã hoàn thành hoặc hủy, không thể xóa.');
        }

        DB::transaction(function () use ($order): void {
            $order->load('orderDetails');
            foreach ($order->orderDetails as $detail) {
                $this->rollbackInventoryAction->execute($detail);
            }
            $order->delete();
        });
    }
}
