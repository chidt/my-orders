<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'site_id',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($tag) {
            // Handle name conflicts by appending a number
            $originalName = $tag->name;
            $counter = 1;
            while (static::where('name', $tag->name)
                ->where('site_id', $tag->site_id)
                ->exists()) {
                $tag->name = $originalName.' ('.$counter.')';
                $counter++;
            }

            // Generate slug from the final name if no slug was provided
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }

            // Handle slug conflicts by appending a number
            $originalSlug = $tag->slug;
            $counter = 1;
            while (static::where('slug', $tag->slug)
                ->where('site_id', $tag->site_id)
                ->exists()) {
                $tag->slug = $originalSlug.'-'.$counter;
                $counter++;
            }
        });

        static::updating(function ($tag) {
            if ($tag->isDirty('name') && empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }

            // Handle name conflicts during updates if name is dirty
            if ($tag->isDirty('name')) {
                $originalName = $tag->name;
                $counter = 1;
                while (static::where('name', $tag->name)
                    ->where('site_id', $tag->site_id)
                    ->where('id', '!=', $tag->id)
                    ->exists()) {
                    $tag->name = $originalName.' ('.$counter.')';
                    $counter++;
                }
            }

            // Handle slug conflicts during updates if slug is dirty
            if ($tag->isDirty('slug')) {
                $originalSlug = $tag->slug;
                $counter = 1;
                while (static::where('slug', $tag->slug)
                    ->where('site_id', $tag->site_id)
                    ->where('id', '!=', $tag->id)
                    ->exists()) {
                    $tag->slug = $originalSlug.'-'.$counter;
                    $counter++;
                }
            }
        });
    }

    /**
     * Retrieve the model for a bound value with site scoping.
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
     * Get the site that owns the tag.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get all products that have this tag.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_tags');
    }

    /**
     * Scope a query to only include tags for a specific site.
     */
    public function scopeForSite(Builder $query, int $siteId): Builder
    {
        return $query->where('site_id', $siteId);
    }

    /**
     * Scope a query to order tags by popularity (usage count).
     */
    public function scopePopular(Builder $query): Builder
    {
        return $query->withCount('products')->orderByDesc('products_count');
    }

    /**
     * Scope a query to order tags by name.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('name');
    }

    /**
     * Get the usage count for this tag (number of products).
     */
    public function getUsageCountAttribute(): int
    {
        return $this->products()->count();
    }

    /**
     * Check if the tag is unused (no products).
     */
    public function isUnused(): bool
    {
        return $this->products()->count() === 0;
    }

    /**
     * Check if tag can be deleted (no products using it).
     */
    public function canDelete(): bool
    {
        return $this->products()->count() === 0;
    }
}
