<?php

namespace App\Actions\Order;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\ProductItem;
use App\Support\OrderCustomerPayload;

class BuildOrderEditPayload
{
    public function execute(Order $order): array
    {
        return [
            'selectedCustomer' => $order->customer ? OrderCustomerPayload::forSearch($order->customer) : null,
            'initialProductItems' => $order->orderDetails
                ->map(fn (OrderDetail $detail) => $detail->productItem)
                ->filter()
                ->unique('id')
                ->values()
                ->map(fn (ProductItem $item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'price' => (float) $item->price,
                    'product_name' => $item->product?->name,
                    'image' => $item->image,
                ])->all(),
            'order' => [
                'id' => $order->id,
                'customer_id' => (string) $order->customer_id,
                'shipping_address_id' => (string) $order->shipping_address_id,
                'order_date' => $order->order_date?->format('Y-m-d\\TH:i'),
                'status_label' => $order->status->label(),
                'status_color' => $order->status->color(),
                'payment_status_label' => $order->payment_status->label(),
                'payment_status_color' => $order->payment_status->color(),
                'sale_channel' => (string) $order->sale_channel,
                'shipping_payer' => (string) $order->shipping_payer,
                'shipping_note' => $order->shipping_note,
                'order_note' => $order->order_note,
                'details' => $order->orderDetails->map(fn (OrderDetail $detail) => [
                    'id' => $detail->id,
                    'product_item_id' => (string) $detail->product_item_id,
                    'status_label' => $detail->status->label(),
                    'status_color' => $detail->status->color(),
                    'payment_status_label' => $detail->payment_status->label(),
                    'payment_status_color' => $detail->payment_status->color(),
                    'qty' => (int) $detail->qty,
                    'discount' => (float) $detail->discount,
                    'addition_price' => (float) $detail->addition_price,
                    'note' => $detail->note ?? '',
                ])->values(),
            ],
        ];
    }
}
