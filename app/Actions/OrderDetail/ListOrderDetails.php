<?php

namespace App\Actions\OrderDetail;

use App\Enums\OrderStatus;
use App\Models\OrderDetail;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class ListOrderDetails
{
    public function execute(Site $site, Request $request): LengthAwarePaginator
    {
        if (! $request->filled('filter_status')) {
            return (new LengthAwarePaginator([], 0, 20, max(1, (int) $request->input('page', 1)), [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]))->appends($request->query());
        }

        try {
            $statusFilter = OrderStatus::from((int) $request->input('filter_status'));
        } catch (\ValueError) {
            return (new LengthAwarePaginator([], 0, 20, max(1, (int) $request->input('page', 1)), [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]))->appends($request->query());
        }

        $query = OrderDetail::query()
            ->where('site_id', $site->id)
            ->where('status', $statusFilter->value)
            ->with([
                'order:id,order_number,order_date,customer_id',
                'order.customer:id,name',
                'productItem:id,name,sku,product_id',
                'productItem.product:id,name,product_type_id',
                'productItem.product.productType:id,name,color',
            ])
            ->latest('id');

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->whereHas('order', fn ($orderQ) => $orderQ->where('order_number', 'like', "%{$search}%"))
                    ->orWhereHas('order.customer', fn ($customerQ) => $customerQ->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('productItem.product', fn ($productQ) => $productQ->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('productItem', function ($itemQ) use ($search) {
                        $itemQ->where('sku', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('customer_id')) {
            $customerId = (int) $request->input('customer_id');
            $query->whereHas('order', fn ($orderQ) => $orderQ->where('customer_id', $customerId));
        }

        if ($request->filled('product_id')) {
            $productId = (int) $request->input('product_id');
            $query->whereHas('productItem.product', fn ($productQ) => $productQ->where('id', $productId));
        }

        if ($request->filled('product_item_id')) {
            $query->where('product_item_id', (int) $request->input('product_item_id'));
        }

        if ($request->filled('product_type_id')) {
            $productTypeId = (int) $request->input('product_type_id');
            $query->whereHas('productItem.product', fn ($productQ) => $productQ->where('product_type_id', $productTypeId));
        }

        if ($request->filled('payment_statuses')) {
            $paymentStatuses = array_filter(array_map('intval', (array) $request->input('payment_statuses')));
            if (count($paymentStatuses) > 0) {
                $query->whereIn('payment_status', $paymentStatuses);
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('order_date', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('order_date', '<=', $request->date('date_to'));
        }

        return $query->paginate(20)->withQueryString();
    }
}
