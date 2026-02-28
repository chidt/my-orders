<?php

namespace App\Actions\Warehouse;

use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class DeleteWarehouse
{
    /**
     * Delete the given warehouse after validating business rules.
     */
    public function execute(Warehouse $warehouse): void
    {
        DB::transaction(function () use ($warehouse) {
            // Check if warehouse can be deleted (no locations)
            if (! $warehouse->canBeDeleted()) {
                throw new \Exception('Không thể xóa kho vì vẫn còn vị trí. Vui lòng xóa tất cả vị trí trước.');
            }

            // Hard delete the warehouse
            $warehouse->delete();
        });
    }
}
