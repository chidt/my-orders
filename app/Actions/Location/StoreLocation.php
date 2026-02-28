<?php

namespace App\Actions\Location;

use App\Models\Location;
use App\Models\Warehouse;
use App\Services\DefaultLocationManager;
use Illuminate\Support\Facades\DB;

class StoreLocation
{
    public function __construct(
        private DefaultLocationManager $defaultLocationManager
    ) {}

    /**
     * Create a new location for the given warehouse.
     */
    public function execute(array $data, Warehouse $warehouse): Location
    {
        return DB::transaction(function () use ($data, $warehouse) {
            $location = new Location([
                'code' => $data['code'],
                'name' => $data['name'],
                'is_default' => $data['is_default'] ?? false,
            ]);

            // Associate with the warehouse
            $location->warehouse()->associate($warehouse);
            $location->save();

            // Handle default location logic
            if ($location->is_default) {
                $this->defaultLocationManager->switchDefaultLocation($location);
            }

            // Ensure warehouse has a default location
            $this->defaultLocationManager->ensureWarehouseHasDefaultLocation($warehouse);

            return $location->fresh();
        });
    }
}
