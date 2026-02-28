<?php

namespace App\Actions\Location;

use App\Models\Location;
use App\Services\DefaultLocationManager;
use Illuminate\Support\Facades\DB;

class DeleteLocation
{
    public function __construct(
        private DefaultLocationManager $defaultLocationManager
    ) {}

    /**
     * Delete a location with proper validation and default location management.
     */
    public function execute(Location $location): void
    {
        // Validate business rules
        if (! $location->canBeDeleted()) {
            throw new \Exception('Không thể xóa vị trí này vì còn tồn kho.');
        }

        if ($location->is_default && ! $this->defaultLocationManager->validateDefaultLocationDeletion($location)) {
            throw new \Exception('Không thể xóa vị trí mặc định duy nhất của kho.');
        }

        DB::transaction(function () use ($location) {
            $warehouse = $location->warehouse;
            $wasDefault = $location->is_default;

            // Delete the location
            $location->delete();

            // Handle default location reassignment if needed
            if ($wasDefault) {
                $this->defaultLocationManager->handleDefaultLocationDeletion($location);
            }
        });
    }
}
