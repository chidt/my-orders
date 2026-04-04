<?php

namespace App\Actions\OrderDetail;

use App\Enums\OrderStatus;
use App\Models\OrderDetail;

class MapOrderDetailListItem
{
    public function execute(OrderDetail $detail): array
    {
        $status = $detail->status;
        $paymentStatus = $detail->payment_status;

        return [
            'id' => $detail->id,
            'qty' => (int) $detail->qty,
            'price' => (float) $detail->price,
            'discount' => (float) $detail->discount,
            'total' => (float) $detail->total,
            'order_date' => $detail->order_date?->format('Y-m-d H:i:s'),
            'status' => $status->value,
            'status_label' => $status->label(),
            'status_color' => $status->color(),
            'payment_status' => $paymentStatus->value,
            'payment_status_label' => $paymentStatus->label(),
            'payment_status_color' => $paymentStatus->color(),
            'payment_request_detail_id' => $detail->payment_request_detail_id !== null
                ? (int) $detail->payment_request_detail_id
                : null,
            'order' => [
                'id' => $detail->order?->id,
                'order_number' => $detail->order?->order_number,
            ],
            'customer' => [
                'id' => $detail->order?->customer?->id,
                'name' => $detail->order?->customer?->name,
            ],
            'product' => [
                'id' => $detail->productItem?->product?->id,
                'name' => $detail->productItem?->product?->name,
            ],
            'product_item' => [
                'id' => $detail->productItem?->id,
                'name' => $detail->productItem?->name,
                'sku' => $detail->productItem?->sku,
                'image' => $detail->productItem?->image,
            ],
            'product_type' => [
                'id' => $detail->productItem?->product?->productType?->id,
                'name' => $detail->productItem?->product?->productType?->name,
                'color' => $detail->productItem?->product?->productType?->color,
            ],
            'can_update_status' => ! $status->isFinal(),
            'allowed_status_values' => collect($status->transitions())
                ->prepend($status)
                ->map(fn (OrderStatus $allowedStatus) => $allowedStatus->value)
                ->values(),
        ];
    }
}
