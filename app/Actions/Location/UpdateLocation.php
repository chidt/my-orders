<?php

namespace App\Actions\Location;

use App\Models\Location;
use App\Services\DefaultLocationManager;
use Illuminate\Support\Facades\DB;

class UpdateLocation
{
    public function __construct(
        private DefaultLocationManager $defaultLocationManager
    ) {}

    /**
     * Update a location with proper default location management.
     */
    public function execute(Location $location, array $data): Location
    {
        return DB::transaction(function () use ($location, $data) {
            $wasDefault = $location->is_default;
            $isNowDefault = $data['is_default'] ?? false;

            // Update the location
            $location->update([
                'code' => $data['code'],
                'name' => $data['name'],
                'is_default' => $isNowDefault,
            ]);

            // Handle default location logic
            if ($isNowDefault && ! $wasDefault) {
                // Switching to default - remove default from other locations
                $this->defaultLocationManager->switchDefaultLocation($location);
            } elseif (! $isNowDefault && $wasDefault) {
                // Removing default - ensure warehouse still has a default
                $this->defaultLocationManager->ensureWarehouseHasDefaultLocation($location->warehouse);
            }

            return $location->fresh();
        });
    }
}
