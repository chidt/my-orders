<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductAttributeValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'value',
        'order',
        'purchase_addition_value',
        'partner_addition_value',
        'addition_value',
        'product_id',
        'attribute_id',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'purchase_addition_value' => 'decimal:2',
            'partner_addition_value' => 'decimal:2',
            'addition_value' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    public function productItemAttributeValues(): HasMany
    {
        return $this->hasMany(ProductItemAttributeValue::class);
    }
}
