<?php

namespace App\Actions\Order;

use App\Models\Order;
use App\Models\Site;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class GetOrders
{
    public function execute(Site $site, Request $request): LengthAwarePaginator
    {
        $query = Order::query()
            ->where('site_id', $site->id)
            ->with('customer:id,name')
            ->latest('order_date')
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', (int) $request->input('status'));
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', (int) $request->input('customer_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('order_date', '>=', (string) $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('order_date', '<=', (string) $request->input('date_to'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->string('search'));
            $query->where(function ($q) use ($search): void {
                $q->where('order_number', 'like', '%'.$search.'%')
                    ->orWhereHas('customer', fn ($customerQ) => $customerQ->where('name', 'like', '%'.$search.'%'));
            });
        }

        return $query->paginate(20)->withQueryString();
    }
}
