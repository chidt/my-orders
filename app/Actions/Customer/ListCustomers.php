<?php

namespace App\Actions\Customer;

use App\Models\Customer;
use App\Models\Site;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListCustomers
{
    public function execute(Site $site, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $search = (string) ($filters['search'] ?? '');
        $type = (string) ($filters['type'] ?? '');
        $sortBy = (string) ($filters['sort_by'] ?? 'name');
        $sortDirection = (string) ($filters['sort_direction'] ?? 'asc');

        $query = Customer::query()
            ->forSite($site->id)
            ->with(['addresses.ward.province'])
            ->withCount('orders');

        if ($search !== '') {
            $query->where(function ($subQuery) use ($search): void {
                $subQuery
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%');
            });
        }

        if ($type !== '' && in_array((int) $type, array_keys(Customer::typeOptions()), true)) {
            $query->where('type', (int) $type);
        }

        if (in_array($sortBy, ['name', 'type', 'created_at'], true)) {
            $query->orderBy($sortBy, $sortDirection === 'desc' ? 'desc' : 'asc');
        } else {
            $query->orderBy('name');
        }

        return $query->paginate($perPage)->withQueryString();
    }
}
