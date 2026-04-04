<?php

namespace App\Actions\OrderDetail;

use App\Models\Product;
use App\Models\ProductItem;
use App\Models\ProductType;
use App\Models\Site;
use App\Models\Supplier;

class BuildOrderDetailFilterOptions
{
    public function execute(Site $site): array
    {
        return [
            'products' => Product::query()->where('site_id', $site->id)->orderBy('name')->get(['id', 'name']),
            'productItems' => ProductItem::query()->where('site_id', $site->id)->orderBy('name')->get(['id', 'name', 'sku', 'product_id']),
            'productTypes' => ProductType::query()->where('site_id', $site->id)->orderBy('name')->get(['id', 'name', 'color']),
            'suppliers' => Supplier::query()->where('site_id', $site->id)->orderBy('name')->get(['id', 'name']),
        ];
    }
}
