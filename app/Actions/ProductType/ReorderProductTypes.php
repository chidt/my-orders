<?php

namespace App\Actions\ProductType;

use App\Models\ProductType;

class ReorderProductTypes
{
    public function handle(int $siteId, array $items): void
    {
        foreach ($items as $item) {
            ProductType::where('id', $item['id'])
                ->where('site_id', $siteId)
                ->update(['order' => $item['order']]);
        }
    }
}
