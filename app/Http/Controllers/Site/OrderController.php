<?php

namespace App\Http\Controllers\Site;

use App\Actions\Order\BuildOrderEditPayload;
use App\Actions\Order\BuildOrderFormOptions;
use App\Actions\Order\BuildOrderShowPayload;
use App\Actions\Order\DeleteOrder;
use App\Actions\Order\GetOrders;
use App\Actions\Order\StoreOrder;
use App\Actions\Order\UpdateOrder;
use App\Actions\OrderDetail\UpdateOrderDetailStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderDetailStatusRequest;
use App\Http\Requests\Order\UpdateOrderRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Site;
use App\Support\OrderCustomerPayload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Site $site, Request $request, GetOrders $action): Response
    {
        Gate::authorize('viewAny', [Order::class, $site]);

        $orders = $action->execute($site, $request)
            ->through(fn (Order $order) => [
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
            ]);

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

    public function show(Site $site, Order $order, BuildOrderShowPayload $buildOrderShowPayload): Response
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
            'order' => $buildOrderShowPayload->execute($order),
            'statusOptions' => OrderStatus::options(),
        ]);
    }

    public function edit(Site $site, Order $order, BuildOrderEditPayload $buildOrderEditPayload): Response|RedirectResponse
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
            ...$buildOrderEditPayload->execute($order),
            'canQuickCreateCustomer' => Gate::allows('create', [Customer::class, $site]),
        ]);
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

    public function bulkDestroy(
        Request $request,
        Site $site,
        DeleteOrder $action
    ): RedirectResponse {
        Gate::authorize('delete', [Order::class, $site]);

        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return back()->with('error', 'Chưa chọn đơn hàng nào để xóa.');
        }

        try {
            DB::transaction(function () use ($ids, $action, $site) {
                $orders = Order::query()
                    ->whereIn('id', $ids)
                    ->where('site_id', $site->id)
                    ->get();

                foreach ($orders as $order) {
                    $action->execute($order);
                }
            });
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return back()->with('error', 'Xóa hàng loạt thất bại: '.$e->getMessage());
        }

        return redirect()
            ->route('orders.index', $site)
            ->with('success', count($ids).' đơn hàng đã được xóa thành công.');
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
}
