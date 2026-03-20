<?php

namespace App\Actions\Supplier;

use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

class UpdateSupplier
{
    /**
     * Update the given supplier.
     */
    public function execute(Supplier $supplier, array $data): Supplier
    {
        return DB::transaction(function () use ($supplier, $data): Supplier {
            $supplier->fill([
                'name' => $data['name'],
                'person_in_charge' => $data['person_in_charge'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'description' => $data['description'] ?? null,
            ]);

            $supplier->save();

            return $supplier;
        });
    }
}
