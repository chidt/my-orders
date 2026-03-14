<?php

namespace App\Models;

use Database\Factories\ProductTypeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductType extends Model
{
    /** @use HasFactory<ProductTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'order',
        'show_on_front',
        'color',
        'site_id',
    ];

    protected function casts(): array
    {
        return [
            'show_on_front' => 'boolean',
            'order' => 'integer',
        ];
    }

    /**
     * Retrieve the model for a bound value.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $field = $field ?? $this->getRouteKeyName();

        // First, try to get site from authenticated user (most reliable in testing)
        $siteId = null;
        if (auth()->check() && auth()->user()->site_id) {
            $siteId = auth()->user()->site_id;
        }

        // If we have a site_id, scope the query
        if ($siteId) {
            return $this->where($field, $value)
                ->where('site_id', $siteId)
                ->first();
        }

        // Fallback to default behavior (this shouldn't happen in normal operation)
        return parent::resolveRouteBinding($value, $field);
    }

    /**
     * Get the site that owns the product type.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get the products for this product type.
     * Note: This relationship will be available when Product model is implemented
     */
    public function products(): HasMany
    {
        // Create a dummy relationship that returns empty results
        // when Product class doesn't exist yet
        if (! class_exists('App\Models\Product')) {
            // Create a relationship using existing columns that will always be empty
            // Using site_id = site_id AND site_id = 0 (impossible condition)
            return $this->hasMany(self::class, 'site_id', 'site_id')->where('site_id', 0);
        }

        return $this->hasMany(\App\Models\Product::class, 'product_type_id');
    }

    /**
     * Scope a query to only include product types for a specific site.
     */
    public function scopeForSite(Builder $query, int $siteId): Builder
    {
        return $query->where('site_id', $siteId);
    }

    /**
     * Scope a query to order by the 'order' field.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order')->orderBy('name');
    }

    /**
     * Scope a query to only include product types shown on front.
     */
    public function scopeShowOnFront(Builder $query): Builder
    {
        return $query->where('show_on_front', true);
    }

    /**
     * Get the count of products using this type.
     * Returns 0 if Product model doesn't exist yet.
     */
    public function getProductsCountAttribute(): int
    {
        if (! class_exists('App\Models\Product')) {
            return 0;
        }

        return $this->products()->count();
    }

    /**
     * Check if this product type can be deleted.
     * Cannot delete if it has products associated with it.
     */
    public function canDelete(): bool
    {
        return $this->products_count === 0;
    }

    /**
     * Get a preview badge HTML for the color.
     */
    public function getColorBadgeAttribute(): string
    {
        return "<span class='inline-block w-4 h-4 rounded-full' style='background-color: {$this->color}'></span>";
    }
}
