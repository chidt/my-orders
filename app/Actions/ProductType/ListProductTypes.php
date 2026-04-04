<?php

namespace App\Actions\ProductType;

use App\Models\ProductType;

class ListProductTypes
{
    public function execute(int $siteId, array $filters = []): array
    {
        $search = (string) ($filters['search'] ?? '');
        $showOnFront = (string) ($filters['show_on_front'] ?? '');
        $sortBy = (string) ($filters['sort_by'] ?? 'order');
        $sortDirection = ((string) ($filters['sort_direction'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

        $query = ProductType::forSite($siteId)->withCount('products');

        if ($search !== '') {
            $query->where('name', 'like', '%'.$search.'%');
        }

        if ($showOnFront !== '') {
            $query->where('show_on_front', filter_var($showOnFront, FILTER_VALIDATE_BOOLEAN));
        }

        if (in_array($sortBy, ['name', 'order', 'products_count'], true)) {
            if ($sortBy === 'products_count') {
                $query->orderBy('products_count', $sortDirection);
            } else {
                $query->orderBy($sortBy, $sortDirection);
            }
        } else {
            $query->ordered();
        }

        $productTypes = $query->paginate(20)->withQueryString();

        return [
            'productTypes' => $productTypes,
        ];
    }
}
