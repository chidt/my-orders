<?php

namespace App\Http\Controllers\Site;

use App\Actions\OrderDetail\BuildOrderDetailFilterOptions;
use App\Actions\OrderDetail\BuildOrderDetailFiltersPayload;
use App\Actions\OrderDetail\BuildOrderDetailShowPayload;
use App\Actions\OrderDetail\BulkUpdateOrderDetailStatus;
use App\Actions\OrderDetail\ListOrderDetails;
use App\Actions\OrderDetail\MapOrderDetailListItem;
use App\Actions\OrderDetail\UpdateOrderDetailPaymentStatus;
use App\Actions\OrderDetail\UpdateOrderDetailStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\BulkUpdateOrderDetailStatusRequest;
use App\Http\Requests\Order\UpdateOrderDetailPaymentStatusRequest;
use App\Http\Requests\Order\UpdateOrderDetailStatusRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Site;
use App\Support\OrderCustomerPayload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class OrderDetailController extends Controller
{
    private function redirectToOrderDetailShow(Site $site, OrderDetail $orderDetail, string $successMessage): RedirectResponse
    {
        return redirect()
            ->route('order-details.show', [$site, $orderDetail])
            ->with('success', $successMessage);
    }

    public function index(
        Site $site,
        Request $request,
        ListOrderDetails $action,
        BuildOrderDetailFiltersPayload $buildFiltersPayload,
        MapOrderDetailListItem $mapOrderDetailListItem,
        BuildOrderDetailFilterOptions $buildFilterOptions
    ): Response {
        Gate::authorize('viewAny', [Order::class, $site]);

        $filtersPayload = $buildFiltersPayload->execute($request);

        $orderDetails = $action->execute($site, $request)
            ->through(fn (OrderDetail $detail) => $mapOrderDetailListItem->execute($detail));

        $filterCustomer = null;
        if ($request->filled('customer_id')) {
            $customer = Customer::query()
                ->where('site_id', $site->id)
                ->find((int) $request->input('customer_id'));
            if ($customer) {
                $filterCustomer = OrderCustomerPayload::forSearch($customer);
            }
        }

        return Inertia::render('Orders/Details/Index', [
            'site' => $site,
            'filters' => $filtersPayload['filters'],
            'activeFilterStatus' => $filtersPayload['activeFilterStatus'],
            'filterStatusTransitions' => $filtersPayload['filterStatusTransitions'],
            'orderDetails' => $orderDetails,
            'statusOptions' => OrderStatus::options(),
            'paymentStatusOptions' => PaymentStatus::options(),
            'filterCustomer' => $filterCustomer,
            ...$buildFilterOptions->execute($site),
        ]);
    }

    public function show(Site $site, OrderDetail $orderDetail, BuildOrderDetailShowPayload $buildShowPayload): Response
    {
        $orderDetail->loadMissing('order');
        abort_if(! $orderDetail->order, 404);
        Gate::authorize('view', [$orderDetail->order, $site]);

        $orderDetail->load([
            'order.customer',
            'order.shippingAddress.ward.province',
            'productItem.product.productType',
        ]);

        return Inertia::render('Orders/Details/Show', [
            'site' => $site,
            'orderDetail' => $buildShowPayload->execute($orderDetail),
            'statusOptions' => OrderStatus::options(),
            'paymentStatusOptions' => PaymentStatus::options(),
        ]);
    }

    public function updateStatus(
        UpdateOrderDetailStatusRequest $request,
        Site $site,
        OrderDetail $orderDetail,
        UpdateOrderDetailStatus $action
    ): RedirectResponse {
        $orderDetail->loadMissing('order');
        abort_if(! $orderDetail->order, 404);
        Gate::authorize('update', [$orderDetail->order, $site]);

        $currentStatus = $orderDetail->status;
        if ($currentStatus->isFinal()) {
            return back()->with('error', 'OrderDetail đã ở trạng thái cuối, không thể cập nhật.');
        }

        $nextStatus = OrderStatus::from((int) $request->integer('status'));
        if ($nextStatus === $currentStatus) {
            $orderDetail->update([
                'note' => $request->string('note')->toString() ?: $orderDetail->note,
            ]);

            return $this->redirectToOrderDetailShow($site, $orderDetail, 'Cập nhật thành công.');
        }

        if (! in_array($nextStatus, $currentStatus->transitions(), true)) {
            return back()->with('error', 'Trạng thái chuyển đổi không hợp lệ.');
        }

        $order = $orderDetail->order()->firstOrFail();
        $orderStatus = $order->status;
        if ($orderStatus->isFinal()) {
            return back()->with('error', 'Order đã hoàn thành hoặc hủy, không thể chỉnh sửa chi tiết.');
        }

        try {
            $action->execute(
                $order,
                $orderDetail,
                (int) $request->integer('status'),
                $request->string('note')->toString() ?: null,
            );
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return back()->with('error', 'Cập nhật thất bại: '.$e->getMessage());
        }

        return $this->redirectToOrderDetailShow($site, $orderDetail, 'Cập nhật thành công.');
    }

    public function updatePaymentStatus(
        UpdateOrderDetailPaymentStatusRequest $request,
        Site $site,
        OrderDetail $orderDetail,
        UpdateOrderDetailPaymentStatus $action
    ): RedirectResponse {
        $orderDetail->loadMissing('order');
        abort_if(! $orderDetail->order, 404);
        Gate::authorize('update', [$orderDetail->order, $site]);

        $nextPaymentStatus = (int) $request->integer('payment_status');
        $currentPaymentStatus = $orderDetail->payment_status;
        if (! $action->canTransition($currentPaymentStatus->value, $nextPaymentStatus)) {
            return back()->with('error', 'Payment status transition không hợp lệ.');
        }

        try {
            $action->execute($orderDetail, $nextPaymentStatus, $request->string('note')->toString() ?: null);
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return back()->with('error', 'Cập nhật trạng thái thanh toán thất bại: '.$e->getMessage());
        }

        return $this->redirectToOrderDetailShow($site, $orderDetail, 'Trạng thái thanh toán đã được cập nhật thành công.');
    }

    public function bulkUpdateStatus(
        BulkUpdateOrderDetailStatusRequest $request,
        Site $site,
        BulkUpdateOrderDetailStatus $action,
        UpdateOrderDetailStatus $updateOrderDetailStatus
    ): RedirectResponse {
        Gate::authorize('viewAny', [Order::class, $site]);

        $result = $action->execute(
            $site,
            (int) $request->integer('status'),
            $request->filled('filter_status') ? (int) $request->integer('filter_status') : null,
            (array) $request->input('order_detail_ids', []),
            $request->string('note')->toString() ?: null,
            $updateOrderDetailStatus,
        );

        if (count($result['failed']) > 0) {
            return back()->with('error', "Bulk update: {$result['success_count']} thành công, ".count($result['failed']).' thất bại. '.implode(' | ', $result['failed']));
        }

        return back()->with('success', "OrderDetails đã được cập nhật thành công: {$result['success_count']}.");
    }
}
