<?php

namespace App\Actions\Product;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListProducts
{
    public function execute(int $siteId, array $filters = []): LengthAwarePaginator
    {
        $query = Product::query()
            ->where('site_id', $siteId)
            ->with([
                'category:id,name',
                'supplier:id,name',
                'unit:id,name',
                'productType:id,name',
                'media',
                'tags' => fn ($q) => $q->where('site_id', $siteId),
            ])
            ->withCount('productItems')
            ->latest('id');

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%')
                    ->orWhere('supplier_code', 'like', '%'.$search.'%');
            });
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }

        if (! empty($filters['product_type_id'])) {
            $query->where('product_type_id', (int) $filters['product_type_id']);
        }

        if (! empty($filters['supplier_id'])) {
            $query->where('supplier_id', (int) $filters['supplier_id']);
        }

        if (! empty($filters['tag_id'])) {
            $tagId = (int) $filters['tag_id'];
            $query->whereHas('tags', function ($q) use ($tagId): void {
                $q->where('tags.id', $tagId);
            });
        }

        return $query->paginate(20)->withQueryString();
    }
}
