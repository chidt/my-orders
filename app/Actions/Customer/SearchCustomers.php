<?php

namespace App\Actions\Customer;

use App\Models\Customer;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SearchCustomers
{
    public function execute(Site $site, Request $request): Collection
    {
        $search = trim($request->string('search')->toString());

        $query = Customer::query()
            ->where('site_id', $site->id)
            ->with(['addresses.ward.province']);

        if (mb_strlen($search) >= 2) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query
            ->orderBy('name')
            ->limit(50)
            ->get();
    }
}
