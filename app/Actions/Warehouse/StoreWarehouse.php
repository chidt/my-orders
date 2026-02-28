<?php

namespace App\Actions\Warehouse;

use App\Models\Site;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class StoreWarehouse
{
    /**
     * Create a new warehouse for the given site.
     */
    public function execute(array $data, Site $site): Warehouse
    {
        return DB::transaction(function () use ($data, $site) {
            $warehouse = new Warehouse([
                'code' => $data['code'],
                'name' => $data['name'],
                'address' => $data['address'],
            ]);

            // Associate with the site
            $warehouse->site()->associate($site);
            $warehouse->save();

            return $warehouse;
        });
    }
}
