<?php

namespace App\Http\Controllers\Site;

use App\Actions\Customer\StoreCustomer;
use App\Actions\Order\BuildOrderFormOptions;
use App\Actions\Order\DeleteOrder;
use App\Actions\Order\StoreOrder;
use App\Actions\Order\UpdateOrder;
use App\Actions\OrderDetail\UpdateOrderDetailStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\QuickStoreCustomerRequest;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderDetailStatusRequest;
use App\Http\Requests\Order\UpdateOrderRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\ProductItem;
use App\Models\Site;
use App\Support\OrderCustomerPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Site $site, Request $request): Response
    {
        Gate::authorize('viewAny', [Order::class, $site]);

        $query = Order::query()
            ->where('site_id', $site->id)
            ->with(['customer:id,name'])
            ->withSum('orderDetails as total_qty', 'qty')
            ->withSum('orderDetails as total_amount', 'total')
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', (int) $request->input('status'));
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', (int) $request->input('customer_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('order_date', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('order_date', '<=', $request->date('date_to'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($customerQ) use ($search) {
                        $customerQ->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $orders = $query
            ->paginate(20)
            ->withQueryString()
            ->through(function (Order $order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'order_date' => $order->order_date?->format('Y-m-d H:i:s'),
                    'status_label' => $order->status->label(),
                    'status_color' => $order->status->color(),
                    'payment_status' => $order->payment_status->value,
                    'payment_status_label' => $order->payment_status->label(),
                    'payment_status_color' => $order->payment_status->color(),
                    'customer' => $order->customer
                        ? ['id' => $order->customer->id, 'name' => $order->customer->name]
                        : null,
                    'total_qty' => (int) $order->total_qty,
                    'total_amount' => (float) $order->total_amount,
                ];
            });

        $filterCustomer = null;
        if ($request->filled('customer_id')) {
            $customer = Customer::query()
                ->where('site_id', $site->id)
                ->find((int) $request->input('customer_id'));
            if ($customer) {
                $filterCustomer = OrderCustomerPayload::forSearch($customer);
            }
        }

        return Inertia::render('Orders/Index', [
            'site' => $site,
            'filters' => [
                'status' => $request->string('status')->toString(),
                'customer_id' => $request->string('customer_id')->toString(),
                'date_from' => $request->string('date_from')->toString(),
                'date_to' => $request->string('date_to')->toString(),
                'search' => $request->string('search')->toString(),
            ],
            'filterCustomer' => $filterCustomer,
            'orders' => $orders,
            'statusOptions' => OrderStatus::options(),
            'paymentStatusOptions' => PaymentStatus::options(),
        ]);
    }

    public function create(Site $site): Response
    {
        Gate::authorize('create', [Order::class, $site]);

        return Inertia::render('Orders/Create', [
            'site' => $site,
            ...app(BuildOrderFormOptions::class)->execute($site),
            'selectedCustomer' => null,
            'initialProductItems' => [],
            'canQuickCreateCustomer' => Gate::allows('create', [Customer::class, $site]),
            'statusOptions' => OrderStatus::options(),
        ]);
    }

    public function store(StoreOrderRequest $request, Site $site, StoreOrder $action): RedirectResponse
    {
        Gate::authorize('create', [Order::class, $site]);

        try {
            $action->execute($site, $request->validated());
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('orders.index', $site)
            ->with('success', 'Đơn hàng đã được tạo thành công.');
    }

    public function show(Site $site, Order $order): Response
    {
        Gate::authorize('view', [$order, $site]);

        $order->load([
            'customer:id,name,phone',
            'shippingAddress.ward.province',
            'orderDetails.productItem.product',
            'orderDetails.productItem.product.media',
        ]);

        return Inertia::render('Orders/Show', [
            'site' => $site,
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'order_date' => $order->order_date?->format('Y-m-d H:i:s'),
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
                'status_color' => $order->status->color(),
                'payment_status' => $order->payment_status->value,
                'payment_status_label' => $order->payment_status->label(),
                'payment_status_color' => $order->payment_status->color(),
                'sale_channel' => (int) $order->sale_channel,
                'shipping_payer' => (int) $order->shipping_payer,
                'order_note' => $order->order_note,
                'shipping_note' => $order->shipping_note,
                'customer' => [
                    'id' => $order->customer?->id,
                    'name' => $order->customer?->name,
                    'phone' => $order->customer?->phone,
                ],
                'shipping_address' => $order->shippingAddress
                    ? [
                        'id' => $order->shippingAddress->id,
                        'address' => $order->shippingAddress->address,
                        'ward' => $order->shippingAddress->ward?->name,
                        'province' => $order->shippingAddress->ward?->province?->name,
                    ]
                    : null,
                'details' => $order->orderDetails->map(function (OrderDetail $detail) {
                    $status = $detail->status instanceof OrderStatus ? $detail->status : OrderStatus::from((int) $detail->status);

                    return [
                        'id' => $detail->id,
                        'status' => $status->value,
                        'status_label' => $status->label(),
                        'qty' => (int) $detail->qty,
                        'price' => (float) $detail->price,
                        'discount' => (float) $detail->discount,
                        'addition_price' => (float) $detail->addition_price,
                        'total' => (float) $detail->total,
                        'note' => $detail->note,
                        'payment_request_detail_id' => $detail->payment_request_detail_id !== null
                            ? (int) $detail->payment_request_detail_id
                            : null,
                        'can_update' => ! $status->isFinal(),
                        'allowed_status_values' => collect($status->transitions())
                            ->prepend($status)
                            ->map(fn (OrderStatus $allowedStatus) => $allowedStatus->value)
                            ->values(),
                        'product_item' => [
                            'id' => $detail->productItem?->id,
                            'name' => $detail->productItem?->name,
                            'sku' => $detail->productItem?->sku,
                            'product_name' => $detail->productItem?->product?->name,
                            'image' => $detail->productItem?->image,
                        ],
                    ];
                })->values(),
            ],
            'statusOptions' => OrderStatus::options(),
        ]);
    }

    public function edit(Site $site, Order $order): Response|RedirectResponse
    {
        Gate::authorize('update', [$order, $site]);

        $currentStatus = $order->status instanceof OrderStatus ? $order->status : OrderStatus::from((int) $order->status);
        if ($currentStatus->isFinal()) {
            return redirect()
                ->route('orders.show', [$site, $order])
                ->with('error', 'Đơn hàng đã hoàn thành hoặc hủy, không thể chỉnh sửa.');
        }

        $order->load([
            'orderDetails.productItem.product',
            'orderDetails.productItem.product.media',
        ]);
        $order->loadMissing(['customer.addresses.ward.province']);

        return Inertia::render('Orders/Edit', [
            'site' => $site,
            ...app(BuildOrderFormOptions::class)->execute($site),
            'selectedCustomer' => $this->transformCustomer($order->customer),
            'initialProductItems' => $order->orderDetails
                ->map(fn (OrderDetail $detail) => $detail->productItem)
                ->filter()
                ->unique('id')
                ->values()
                ->map(fn (ProductItem $item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'price' => (float) $item->price,
                    'product_name' => $item->product?->name,
                    'image' => $item->image,
                ])->all(),
            'canQuickCreateCustomer' => Gate::allows('create', [Customer::class, $site]),
            'order' => [
                'id' => $order->id,
                'customer_id' => (string) $order->customer_id,
                'shipping_address_id' => (string) $order->shipping_address_id,
                'order_date' => $order->order_date?->format('Y-m-d\TH:i'),
                'sale_channel' => (string) $order->sale_channel,
                'shipping_payer' => (string) $order->shipping_payer,
                'shipping_note' => $order->shipping_note,
                'order_note' => $order->order_note,
                'details' => $order->orderDetails->map(fn (OrderDetail $detail) => [
                    'id' => $detail->id,
                    'product_item_id' => (string) $detail->product_item_id,
                    'qty' => (int) $detail->qty,
                    'discount' => (float) $detail->discount,
                    'addition_price' => (float) $detail->addition_price,
                    'note' => $detail->note ?? '',
                ])->values(),
            ],
        ]);
    }

    public function searchCustomers(Site $site, Request $request): JsonResponse
    {
        Gate::authorize('viewAny', [Order::class, $site]);

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

        $customers = $query
            ->orderBy('name')
            ->limit(50)
            ->get()
            ->map(fn (Customer $customer) => $this->transformCustomer($customer))
            ->values();

        return response()->json(['data' => $customers]);
    }

    public function quickStoreCustomer(QuickStoreCustomerRequest $request, Site $site, StoreCustomer $action): JsonResponse
    {
        Gate::authorize('viewAny', [Order::class, $site]);

        try {
            $customer = $action->execute($request->validated(), $site);
            $customer->load(['addresses.ward.province']);
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'message' => 'Khách hàng đã được tạo thành công.',
            'customer' => $this->transformCustomer($customer),
        ], 201);
    }

    public function searchProductItems(Site $site, Request $request): JsonResponse
    {
        Gate::authorize('viewAny', [Order::class, $site]);

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

        $items = $query
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'sku', 'price', 'media_id', 'is_parent_image', 'site_id', 'product_id'])
            ->map(fn (ProductItem $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'sku' => $item->sku,
                'price' => (float) $item->price,
                'product_name' => $item->product?->name,
                'image' => $item->image, // This will use the accessor
            ])
            ->values();

        return response()->json(['data' => $items]);
    }

    public function update(
        UpdateOrderRequest $request,
        Site $site,
        Order $order,
        UpdateOrder $action
    ): RedirectResponse {
        Gate::authorize('update', [$order, $site]);

        try {
            $action->execute($site, $order, $request->validated());
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('orders.show', [$site, $order])
            ->with('success', 'Đơn hàng đã được cập nhật thành công.');
    }

    public function destroy(
        Site $site,
        Order $order,
        DeleteOrder $action
    ): RedirectResponse {
        Gate::authorize('delete', [$order, $site]);

        try {
            $action->execute($order);
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('orders.index', $site)
            ->with('success', 'Đơn hàng đã được xóa thành công.');
    }

    public function updateDetailStatus(
        UpdateOrderDetailStatusRequest $request,
        Site $site,
        Order $order,
        OrderDetail $detail,
        UpdateOrderDetailStatus $action
    ): RedirectResponse {
        Gate::authorize('update', [$order, $site]);

        $orderStatus = $order->status instanceof OrderStatus ? $order->status : OrderStatus::from((int) $order->status);
        if ($orderStatus->isFinal()) {
            return back()->with('error', 'Đơn hàng đã hoàn thành hoặc hủy, không thể chỉnh sửa.');
        }

        if ($detail->order_id !== $order->id || (int) $detail->site_id !== (int) $site->id) {
            abort(404);
        }

        $currentStatus = $detail->status instanceof OrderStatus ? $detail->status : OrderStatus::from((int) $detail->status);
        if ($currentStatus->isFinal()) {
            return back()->with('error', 'OrderDetail đã ở trạng thái cuối, không thể cập nhật.');
        }

        $nextStatus = OrderStatus::from((int) $request->integer('status'));
        if ($nextStatus === $currentStatus) {
            $detail->update([
                'note' => $request->string('note')->toString() ?: $detail->note,
            ]);

            return back()->with('success', 'Cập nhật thành công.');
        }

        if (! in_array($nextStatus, $currentStatus->transitions(), true)) {
            return back()->with('error', 'Trạng thái chuyển đổi không hợp lệ.');
        }

        try {
            $action->execute(
                $order,
                $detail,
                (int) $request->integer('status'),
                $request->string('note')->toString() ?: null
            );
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return back()->with('error', 'Cập nhật thất bại: '.$e->getMessage());
        }

        return back()->with('success', 'Cập nhật thành công.');
    }

    private function transformCustomer(?Customer $customer): ?array
    {
        if (! $customer) {
            return null;
        }

        return OrderCustomerPayload::forSearch($customer);
    }
}
