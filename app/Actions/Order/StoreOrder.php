<?php

namespace App\Actions\Order;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Site;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class StoreOrder
{
    public function __construct(
        private CreateOrderDetail $createOrderDetailAction
    ) {}

    public function execute(Site $site, array $validated): Order
    {
        $customer = Customer::query()
            ->where('site_id', $site->id)
            ->with('addresses')
            ->findOrFail((int) $validated['customer_id']);

        $shippingAddress = $customer->addresses->firstWhere('id', (int) $validated['shipping_address_id']);
        if (! $shippingAddress) {
            throw new \DomainException('Địa chỉ giao hàng không hợp lệ.');
        }

        return DB::transaction(function () use ($validated, $site, $customer, $shippingAddress): Order {
            $order = Order::query()->create([
                'order_number' => $this->generateOrderNumber(),
                'order_date' => $validated['order_date'] ?? CarbonImmutable::now()->format('Y-m-d H:i:s'),
                'customer_type' => $customer->type?->value ?? (int) $customer->type,
                'status' => OrderStatus::New->value,
                'sale_channel' => (int) $validated['sale_channel'],
                'shipping_payer' => (int) $validated['shipping_payer'],
                'phone' => $customer->phone,
                'shipping_note' => $validated['shipping_note'] ?? null,
                'order_note' => $validated['order_note'] ?? null,
                'shipping_address_id' => $shippingAddress->id,
                'customer_id' => $customer->id,
                'site_id' => $site->id,
                'payment_status' => PaymentStatus::Unpaid->value,
            ]);

            foreach ($validated['details'] as $itemData) {
                $this->createOrderDetailAction->execute($site, $order, $itemData, false);
            }

            return $order;
        });
    }

    private function generateOrderNumber(): string
    {
        return now()->timestamp.random_int(100, 999);
    }
}
