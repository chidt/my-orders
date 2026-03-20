<?php

namespace App\Actions\Supplier;

use App\Models\Site;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

class StoreSupplier
{
    /**
     * Create a new supplier for the given site.
     */
    public function execute(array $data, Site $site): Supplier
    {
        return DB::transaction(function () use ($data, $site): Supplier {
            $supplier = new Supplier([
                'name' => $data['name'],
                'person_in_charge' => $data['person_in_charge'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'description' => $data['description'] ?? null,
            ]);

            $supplier->site()->associate($site);
            $supplier->save();

            return $supplier;
        });
    }
}
