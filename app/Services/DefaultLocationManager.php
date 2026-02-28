<?php

namespace App\Services;

use App\Models\Location;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class DefaultLocationManager
{
    /**
     * Ensure the warehouse has exactly one default location.
     */
    public function ensureWarehouseHasDefaultLocation(Warehouse $warehouse): void
    {
        $defaultLocation = $warehouse->locations()->where('is_default', true)->first();

        if (! $defaultLocation) {
            // If no default location exists, make the first location default
            $firstLocation = $warehouse->locations()->oldest()->first();

            if ($firstLocation) {
                $firstLocation->update(['is_default' => true]);
            }
        }
    }

    /**
     * Switch the default location to a new one, ensuring only one default per warehouse.
     */
    public function switchDefaultLocation(Location $newDefault): void
    {
        if (! $newDefault->is_default) {
            return;
        }

        DB::transaction(function () use ($newDefault) {
            // Remove default from all other locations in the same warehouse
            Location::where('warehouse_id', $newDefault->warehouse_id)
                ->where('id', '!=', $newDefault->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        });
    }

    /**
     * Validate if a default location can be deleted.
     */
    public function validateDefaultLocationDeletion(Location $location): bool
    {
        if (! $location->is_default) {
            return true;
        }

        // Check if this is the only location in the warehouse
        $locationCount = Location::where('warehouse_id', $location->warehouse_id)->count();

        return $locationCount > 1;
    }

    /**
     * Get the default location for a warehouse.
     */
    public function getDefaultLocationForWarehouse(Warehouse $warehouse): ?Location
    {
        return $warehouse->locations()->where('is_default', true)->first();
    }

    /**
     * Create a default location for a warehouse if it doesn't have any locations.
     */
    public function createDefaultLocationForWarehouse(Warehouse $warehouse): Location
    {
        return Location::create([
            'code' => Location::generateUniqueCode($warehouse),
            'name' => 'Vị trí mặc định',
            'is_default' => true,
            'warehouse_id' => $warehouse->id,
        ]);
    }

    /**
     * Handle default location reassignment after deletion.
     */
    public function handleDefaultLocationDeletion(Location $deletedLocation): void
    {
        if (! $deletedLocation->is_default) {
            return;
        }

        // Find another location in the same warehouse to make default
        $newDefault = Location::where('warehouse_id', $deletedLocation->warehouse_id)
            ->where('id', '!=', $deletedLocation->id)
            ->oldest()
            ->first();

        if ($newDefault) {
            $newDefault->update(['is_default' => true]);
        }
    }
}
