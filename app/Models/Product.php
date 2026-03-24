<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Product extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $fillable = [
        'name',
        'code',
        'supplier_code',
        'product_type_id',
        'description',
        'qty_in_stock',
        'weight',
        'price',
        'partner_price',
        'purchase_price',
        'supplier_id',
        'unit_id',
        'category_id',
        'order_closing_date',
        'default_location_id',
        'site_id',
        'media_id',
        'extra_attributes',
    ];

    protected function casts(): array
    {
        return [
            'product_type_id' => 'integer',
            'qty_in_stock' => 'integer',
            'weight' => 'decimal:2',
            'price' => 'decimal:2',
            'partner_price' => 'decimal:2',
            'purchase_price' => 'decimal:2',
            'order_closing_date' => 'datetime',
            'extra_attributes' => 'array',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function defaultLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'default_location_id');
    }

    public function productType(): BelongsTo
    {
        return $this->belongsTo(ProductType::class, 'product_type_id');
    }

    public function productItems(): HasMany
    {
        return $this->hasMany(ProductItem::class);
    }

    public function productAttributeValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'product_tags');
    }
}
