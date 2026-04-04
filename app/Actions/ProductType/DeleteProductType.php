<?php

namespace App\Actions\ProductType;

use App\Models\ProductType;

class DeleteProductType
{
    public function handle(ProductType $productType): void
    {
        if (! $productType->canDelete()) {
            throw new \Exception('Loại sản phẩm này không thể xóa vì có sản phẩm đang sử dụng.');
        }

        $productType->delete();
    }
}
