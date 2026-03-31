<?php

namespace App\Actions\Order;

use App\Actions\OrderDetail\ManageOrderDetailInventory;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\ProductItem;
use App\Models\Site;

class CreateOrderDetail
{
    public function __construct(
        private ManageOrderDetailInventory $inventoryAction
    ) {}

    public function execute(Site $site, Order $order, array $itemData, bool $applyInventory = true): OrderDetail
    {
        $productItem = ProductItem::query()
            ->where('site_id', $site->id)
            ->findOrFail((int) $itemData['product_item_id']);

        $qty = (int) $itemData['qty'];
        $price = (float) $productItem->price;
        $discount = (float) ($itemData['discount'] ?? 0);
        $additionPrice = (float) ($itemData['addition_price'] ?? 0);
        $total = ($qty * $price) - $discount + $additionPrice;

        $detailStatus = OrderStatus::New;
        if ($applyInventory) {
            $availableQty = $this->inventoryAction->getAvailableQty($site->id, $productItem->id);
            $detailStatus = $availableQty >= $qty
                ? OrderStatus::Ordered
                : OrderStatus::PreOrder;
        }

        $detail = OrderDetail::query()->create([
            'order_id' => $order->id,
            'site_id' => $site->id,
            'product_item_id' => $productItem->id,
            'status' => $detailStatus->value,
            'payment_status' => 1,
            'qty' => $qty,
            'price' => $price,
            'discount' => $discount,
            'addition_price' => $additionPrice,
            'total' => $total,
            'note' => $itemData['note'] ?? null,
            'order_date' => $order->order_date,
        ]);

        if ($applyInventory) {
            if ($detailStatus === OrderStatus::Ordered) {
                $this->inventoryAction->reserveForDetail($detail);
            } else {
                $this->inventoryAction->createPreOrder($detail);
            }
        }

        return $detail;
    }
}
