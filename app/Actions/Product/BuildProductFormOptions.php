<?php

namespace App\Actions\Product;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\Location;
use App\Models\ProductType;
use App\Models\Supplier;
use App\Models\Tag;
use App\Models\Unit;
use App\Models\Warehouse;

class BuildProductFormOptions
{
    public function execute(int $siteId): array
    {
        $categories = Category::forSite($siteId)
            ->ordered()
            ->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => implode(' > ', $category->breadcrumb),
            ]);

        $suppliers = Supplier::query()
            ->where('site_id', $siteId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $productTypes = ProductType::forSite($siteId)
            ->ordered()
            ->get(['id', 'name']);

        $units = Unit::query()
            ->orderBy('name')
            ->get(['id', 'name', 'unit']);

        $warehouses = Warehouse::forSite($siteId)
            ->with(['locations' => fn ($q) => $q->orderBy('is_default', 'desc')->orderBy('name')])
            ->orderBy('name')
            ->get();

        $locations = $warehouses->flatMap(function (Warehouse $warehouse) {
            return $warehouse->locations->map(fn (Location $location) => [
                'id' => $location->id,
                'name' => $warehouse->name.' - '.$location->name,
                'is_default' => (bool) $location->is_default,
            ]);
        })->values();

        $attributes = Attribute::query()
            ->where('site_id', $siteId)
            ->orderBy('order')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'order']);

        $tags = Tag::forSite($siteId)
            ->ordered()
            ->get(['id', 'name']);

        return [
            'categories' => $categories,
            'suppliers' => $suppliers,
            'productTypes' => $productTypes,
            'units' => $units,
            'locations' => $locations,
            'attributes' => $attributes,
            'tags' => $tags,
        ];
    }
}
