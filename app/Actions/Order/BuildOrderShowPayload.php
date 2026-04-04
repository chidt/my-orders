<?php

namespace App\Actions\Order;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderDetail;

class BuildOrderShowPayload
{
    public function execute(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'order_date' => $order->order_date?->format('Y-m-d H:i:s'),
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'status_color' => $order->status->color(),
            'payment_status' => $order->payment_status->value,
            'payment_status_label' => $order->payment_status->label(),
            'payment_status_color' => $order->payment_status->color(),
            'sale_channel' => (int) $order->sale_channel,
            'shipping_payer' => (int) $order->shipping_payer,
            'order_note' => $order->order_note,
            'shipping_note' => $order->shipping_note,
            'customer' => [
                'id' => $order->customer?->id,
                'name' => $order->customer?->name,
                'phone' => $order->customer?->phone,
            ],
            'shipping_address' => $order->shippingAddress
                ? [
                    'id' => $order->shippingAddress->id,
                    'address' => $order->shippingAddress->address,
                    'ward' => $order->shippingAddress->ward?->name,
                    'province' => $order->shippingAddress->ward?->province?->name,
                ]
                : null,
            'details' => $order->orderDetails->map(function (OrderDetail $detail) {
                $status = $detail->status instanceof OrderStatus ? $detail->status : OrderStatus::from((int) $detail->status);
                $paymentStatus = $detail->payment_status instanceof PaymentStatus ? $detail->payment_status : PaymentStatus::from((int) $detail->payment_status);

                return [
                    'id' => $detail->id,
                    'status' => $status->value,
                    'status_label' => $status->label(),
                    'status_color' => $status->color(),
                    'payment_status' => $paymentStatus->value,
                    'payment_status_label' => $paymentStatus->label(),
                    'payment_status_color' => $paymentStatus->color(),
                    'qty' => (int) $detail->qty,
                    'price' => (float) $detail->price,
                    'discount' => (float) $detail->discount,
                    'addition_price' => (float) $detail->addition_price,
                    'total' => (float) $detail->total,
                    'note' => $detail->note,
                    'payment_request_detail_id' => $detail->payment_request_detail_id !== null
                        ? (int) $detail->payment_request_detail_id
                        : null,
                    'can_update' => ! $status->isFinal(),
                    'allowed_status_values' => collect($status->transitions())
                        ->prepend($status)
                        ->map(fn (OrderStatus $allowedStatus) => $allowedStatus->value)
                        ->values(),
                    'product_item' => [
                        'id' => $detail->productItem?->id,
                        'name' => $detail->productItem?->name,
                        'sku' => $detail->productItem?->sku,
                        'product_name' => $detail->productItem?->product?->name,
                        'image' => $detail->productItem?->image,
                    ],
                ];
            })->values(),
        ];
    }
}
