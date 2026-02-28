<?php

namespace App\Actions\Warehouse;

use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class UpdateWarehouse
{
    /**
     * Update the given warehouse with new data.
     */
    public function execute(Warehouse $warehouse, array $data): Warehouse
    {
        return DB::transaction(function () use ($warehouse, $data) {
            $warehouse->update([
                'code' => $data['code'],
                'name' => $data['name'],
                'address' => $data['address'],
            ]);

            return $warehouse->fresh();
        });
    }
}
