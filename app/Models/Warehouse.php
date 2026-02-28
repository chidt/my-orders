<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'code',
        'name',
        'address',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the site that owns the warehouse.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get the locations for the warehouse.
     */
    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    /**
     * Scope a query to only include warehouses for a specific site.
     */
    public function scopeForSite($query, $siteId)
    {
        return $query->where('site_id', $siteId);
    }

    /**
     * Scope a query to include locations count.
     */
    public function scopeWithLocationsCount($query)
    {
        return $query->withCount('locations');
    }

    /**
     * Get the default location for this warehouse.
     */
    public function getDefaultLocationAttribute()
    {
        return $this->locations()->where('is_default', true)->first();
    }

    /**
     * Check if warehouse can be deleted (no locations).
     */
    public function canBeDeleted(): bool
    {
        return $this->locations()->count() === 0;
    }

    /**
     * Generate a unique warehouse code for the given site.
     */
    public static function generateUniqueCode(Site $site, string $prefix = 'W'): string
    {
        $counter = 1;
        do {
            $code = $prefix.str_pad($counter, 3, '0', STR_PAD_LEFT);
            $counter++;
        } while (self::where('site_id', $site->id)->where('code', $code)->exists());

        return $code;
    }
}
