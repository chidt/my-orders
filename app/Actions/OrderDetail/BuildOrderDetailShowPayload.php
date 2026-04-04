<?php

namespace App\Actions\OrderDetail;

use App\Enums\OrderStatus;
use App\Models\OrderDetail;

class BuildOrderDetailShowPayload
{
    public function execute(OrderDetail $orderDetail): array
    {
        $status = $orderDetail->status;
        $paymentStatus = $orderDetail->payment_status;

        $statusHistory = collect([
            [
                'title' => 'Tạo chi tiết đơn hàng',
                'status' => $status->label(),
                'note' => $orderDetail->note,
                'at' => $orderDetail->created_at?->format('Y-m-d H:i:s'),
            ],
            [
                'title' => 'Cập nhật gần nhất',
                'status' => $status->label(),
                'note' => $orderDetail->note,
                'at' => $orderDetail->updated_at?->format('Y-m-d H:i:s'),
            ],
        ])->filter(fn ($item) => ! empty($item['at']))->values();

        return [
            'id' => $orderDetail->id,
            'order' => [
                'id' => $orderDetail->order?->id,
                'order_number' => $orderDetail->order?->order_number,
                'order_date' => $orderDetail->order?->order_date?->format('Y-m-d H:i:s'),
                'status' => $orderDetail->order?->status->label(),
            ],
            'customer' => [
                'id' => $orderDetail->order?->customer?->id,
                'name' => $orderDetail->order?->customer?->name,
                'phone' => $orderDetail->order?->customer?->phone,
                'email' => $orderDetail->order?->customer?->email,
            ],
            'shipping_address' => $orderDetail->order?->shippingAddress
                ? [
                    'address' => $orderDetail->order->shippingAddress->address,
                    'ward' => $orderDetail->order->shippingAddress->ward?->name,
                    'province' => $orderDetail->order->shippingAddress->ward?->province?->name,
                ]
                : null,
            'product' => [
                'id' => $orderDetail->productItem?->product?->id,
                'name' => $orderDetail->productItem?->product?->name,
                'type' => $orderDetail->productItem?->product?->productType?->name,
                'type_color' => $orderDetail->productItem?->product?->productType?->color,
            ],
            'product_item' => [
                'id' => $orderDetail->productItem?->id,
                'name' => $orderDetail->productItem?->name,
                'sku' => $orderDetail->productItem?->sku,
                'image' => $orderDetail->productItem?->image,
            ],
            'pricing' => [
                'qty' => (int) $orderDetail->qty,
                'price' => (float) $orderDetail->price,
                'discount' => (float) $orderDetail->discount,
                'addition_price' => (float) $orderDetail->addition_price,
                'total' => (float) $orderDetail->total,
            ],
            'status' => [
                'value' => $status->value,
                'label' => $status->label(),
                'color' => $status->color(),
                'can_update' => ! $status->isFinal(),
                'allowed_status_values' => collect($status->transitions())
                    ->prepend($status)
                    ->map(fn (OrderStatus $allowedStatus) => $allowedStatus->value)
                    ->values(),
            ],
            'payment_status' => [
                'value' => $paymentStatus->value,
                'label' => $paymentStatus->label(),
                'color' => $paymentStatus->color(),
            ],
            'payment_request_detail_id' => $orderDetail->payment_request_detail_id !== null
                ? (int) $orderDetail->payment_request_detail_id
                : null,
            'notes' => [
                'order_detail_note' => $orderDetail->note,
                'order_note' => $orderDetail->order?->order_note,
                'shipping_note' => $orderDetail->order?->shipping_note,
            ],
            'status_history' => $statusHistory,
        ];
    }
}
