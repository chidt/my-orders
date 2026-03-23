<?php

namespace App\Models;

use Database\Factories\AttributeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attribute extends Model
{
    /** @use HasFactory<AttributeFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'order',
        'site_id',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
        ];
    }

    /**
     * Retrieve the model for a bound value.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $field = $field ?? $this->getRouteKeyName();

        $siteId = null;
        if (auth()->check() && auth()->user()->site_id) {
            $siteId = auth()->user()->site_id;
        }

        if ($siteId) {
            return $this->where($field, $value)
                ->where('site_id', $siteId)
                ->first();
        }

        return parent::resolveRouteBinding($value, $field);
    }

    /**
     * Get the site that owns the attribute.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get the product attribute values for this attribute.
     */
    public function productAttributeValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class);
    }

    /**
     * Scope a query to only include attributes for a specific site.
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
     * Check if this attribute can be deleted.
     * Cannot delete if it has product attribute values associated with it.
     */
    public function canBeDeleted(): bool
    {
        return ! $this->productAttributeValues()->exists();
    }
}
