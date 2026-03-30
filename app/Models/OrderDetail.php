<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_status',
        'payment_request_detail_id',
        'status',
        'fulfillment_status',
        'qty',
        'price',
        'discount',
        'addition_price',
        'total',
        'note',
        'product_item_id',
        'order_id',
        'site_id',
        'purchase_request_detail_id',
        'order_date',
        'expected_fulfillment_date',
        'extra_attributes',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'price' => 'decimal:2',
            'discount' => 'decimal:2',
            'addition_price' => 'decimal:2',
            'total' => 'decimal:2',
            'extra_attributes' => 'array',
            'order_date' => 'datetime',
            'expected_fulfillment_date' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function productItem(): BelongsTo
    {
        return $this->belongsTo(ProductItem::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
