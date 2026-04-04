<?php

namespace App\Actions\Product;

use App\Models\ProductItem;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SearchProductItems
{
    public function execute(Site $site, Request $request): Collection
    {
        $search = trim($request->string('search')->toString());

        $query = ProductItem::query()
            ->where('site_id', $site->id)
            ->with(['product:id,name', 'product.media']);

        if (mb_strlen($search) >= 2) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhereHas('product', fn ($productQuery) => $productQuery->where('name', 'like', "%{$search}%"));
            });
        }

        return $query
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'sku', 'price', 'media_id', 'is_parent_image', 'site_id', 'product_id']);
    }
}
