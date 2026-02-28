<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Location extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'code',
        'name',
        'is_default',
        'warehouse_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_default' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the warehouse that owns the location.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Scope a query to only include locations for a specific warehouse.
     */
    public function scopeForWarehouse($query, $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    /**
     * Scope a query to only include default locations.
     */
    public function scopeDefaults($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope a query to include stock information.
     */
    public function scopeWithStock($query)
    {
        // For now, return 0 stock - will be implemented with inventory system
        return $query->selectRaw('locations.*, COALESCE(0, 0) as qty_in_stock');
    }

    /**
     * Check if this location can be deleted.
     */
    public function canBeDeleted(): bool
    {
        // Cannot delete if it has inventory (future implementation)
        // For now, allow deletion but will add inventory check later
        return true;
    }

    /**
     * Check if this is the only default location in the warehouse.
     */
    public function isOnlyDefaultInWarehouse(): bool
    {
        return $this->is_default &&
               self::where('warehouse_id', $this->warehouse_id)
                   ->where('is_default', true)
                   ->where('id', '!=', $this->id)
                   ->count() === 0;
    }

    /**
     * Get the qty_in_stock attribute.
     * This will be calculated from inventory transactions in the future.
     */
    public function getQtyInStockAttribute(): int
    {
        // For now, return 0 - will be implemented with inventory system
        return 0;
    }

    /**
     * Generate a unique location code for the given warehouse.
     */
    public static function generateUniqueCode(Warehouse $warehouse, string $prefix = 'A'): string
    {
        $counter = 1;
        do {
            $code = $prefix.str_pad($counter, 2, '0', STR_PAD_LEFT);
            $counter++;
        } while (self::where('warehouse_id', $warehouse->id)->where('code', $code)->exists());

        return $code;
    }
}
