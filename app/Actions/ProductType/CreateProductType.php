<?php

namespace App\Actions\ProductType;

use App\Models\ProductType;

class CreateProductType
{
    public function handle(array $validatedData, int $siteId): ProductType
    {
        // Ensure we do not try to write or query a non-existent slug column
        unset($validatedData['slug']);

        // Set site_id
        $validatedData['site_id'] = $siteId;

        // Set order to be last
        $validatedData['order'] = ProductType::forSite($siteId)->max('order') + 1;

        return ProductType::create($validatedData);
    }
}
