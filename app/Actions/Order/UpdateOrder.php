<?php

namespace App\Actions\Order;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Site;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class UpdateOrder
{
    public function __construct(
        private RollbackOrderDetailInventory $rollbackInventoryAction,
        private CreateOrderDetail $createOrderDetailAction,
        private SyncOrderStatusFromDetails $syncOrderStatusAction
    ) {}

    public function execute(Site $site, Order $order, array $validated): Order
    {
        $status = $order->status instanceof OrderStatus ? $order->status : OrderStatus::from((int) $order->status);
        if ($status->isFinal()) {
            throw new \DomainException('Đơn hàng đã hoàn thành hoặc hủy, không thể chỉnh sửa.');
        }

        $customer = Customer::query()
            ->where('site_id', $site->id)
            ->with('addresses')
            ->findOrFail((int) $validated['customer_id']);

        $shippingAddress = $customer->addresses->firstWhere('id', (int) $validated['shipping_address_id']);
        if (! $shippingAddress) {
            throw new \DomainException('Địa chỉ giao hàng không hợp lệ.');
        }

        return DB::transaction(function () use ($site, $order, $validated, $customer, $shippingAddress): Order {
            $order->load('orderDetails');
            foreach ($order->orderDetails as $detail) {
                $this->rollbackInventoryAction->execute($detail);
            }
            $order->orderDetails()->delete();

            $order->update([
                'order_date' => $validated['order_date'] ?? CarbonImmutable::now()->format('Y-m-d H:i:s'),
                'customer_type' => $customer->type?->value ?? (int) $customer->type,
                'sale_channel' => (int) $validated['sale_channel'],
                'shipping_payer' => (int) $validated['shipping_payer'],
                'phone' => $customer->phone,
                'shipping_note' => $validated['shipping_note'] ?? null,
                'order_note' => $validated['order_note'] ?? null,
                'shipping_address_id' => $shippingAddress->id,
                'customer_id' => $customer->id,
            ]);

            foreach ($validated['details'] as $itemData) {
                $this->createOrderDetailAction->execute($site, $order, $itemData);
            }

            $this->syncOrderStatusAction->execute($order->fresh());

            return $order;
        });
    }
}
