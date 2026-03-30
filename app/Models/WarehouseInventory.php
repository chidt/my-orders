<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseInventory extends Model
{
    use HasFactory;

    protected $table = 'warehouse_inventory';

    protected $fillable = [
        'product_item_id',
        'location_id',
        'current_qty',
        'reserved_qty',
        'pre_order_qty',
        'avg_cost',
        'site_id',
        'metadata',
        'last_updated',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'last_updated' => 'datetime',
            'avg_cost' => 'decimal:2',
        ];
    }

    public function productItem(): BelongsTo
    {
        return $this->belongsTo(ProductItem::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
