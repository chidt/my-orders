<?php

namespace App\Actions\ProductType;

use App\Models\ProductType;

class UpdateProductType
{
    public function handle(ProductType $productType, array $validatedData): ProductType
    {
        // Ensure we do not try to write or query a non-existent slug column
        unset($validatedData['slug']);

        $productType->update($validatedData);

        return $productType;
    }
}
