<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ProductItem extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $fillable = [
        'name',
        'sku',
        'is_parent_image',
        'is_parent_slider_image',
        'qty_in_stock',
        'price',
        'partner_price',
        'purchase_price',
        'media_id',
        'product_id',
        'site_id',
    ];

    protected function casts(): array
    {
        return [
            'is_parent_image' => 'boolean',
            'is_parent_slider_image' => 'boolean',
            'qty_in_stock' => 'integer',
            'price' => 'decimal:2',
            'partner_price' => 'decimal:2',
            'purchase_price' => 'decimal:2',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productItemAttributeValues(): HasMany
    {
        return $this->hasMany(ProductItemAttributeValue::class);
    }

    public function orderDetails(): HasMany
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function purchaseRequestDetails(): HasMany
    {
        return $this->hasMany(PurchaseRequestDetail::class);
    }

    public function shoppingCartItems(): HasMany
    {
        return $this->hasMany(ShoppingCartItem::class);
    }

    public function warehouseOutDetails(): HasMany
    {
        return $this->hasMany(WarehouseOutDetail::class);
    }

    public function warehouseReceiptDetails(): HasMany
    {
        return $this->hasMany(WarehouseReceiptDetail::class);
    }

    public function warehouseInventory(): HasMany
    {
        return $this->hasMany(WarehouseInventory::class);
    }
}
