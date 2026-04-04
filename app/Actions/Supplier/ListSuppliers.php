<?php

namespace App\Actions\Supplier;

use App\Models\Site;
use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListSuppliers
{
    public function execute(Site $site, array $filters): LengthAwarePaginator
    {
        $search = (string) ($filters['search'] ?? '');
        $sortBy = (string) ($filters['sort_by'] ?? 'name');
        $sortDirection = ((string) ($filters['sort_direction'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

        $query = Supplier::query()
            ->where('site_id', $site->id)
            ->withCount('products');

        if ($search !== '') {
            $query->where(function ($subQuery) use ($search): void {
                $subQuery
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%')
                    ->orWhere('person_in_charge', 'like', '%'.$search.'%');
            });
        }

        if (in_array($sortBy, ['name', 'products_count', 'created_at'], true)) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('name');
        }

        return $query->paginate(20)->withQueryString();
    }
}
