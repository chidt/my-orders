<?php

namespace App\Http\Controllers\Site;

use App\Actions\OrderDetail\ListOrderDetails;
use App\Actions\OrderDetail\UpdateOrderDetailPaymentStatus;
use App\Actions\OrderDetail\UpdateOrderDetailStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\BulkUpdateOrderDetailStatusRequest;
use App\Http\Requests\Order\UpdateOrderDetailPaymentStatusRequest;
use App\Http\Requests\Order\UpdateOrderDetailStatusRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\ProductItem;
use App\Models\ProductType;
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

    private const PAYMENT_STATUS_OPTIONS = [
        ['value' => '1', 'label' => 'Chưa thanh toán'],
        ['value' => '2', 'label' => 'Yêu cầu thanh toán'],
        ['value' => '3', 'label' => 'Đã thanh toán'],
        ['value' => '4', 'label' => 'Đang xử lý'],
        ['value' => '5', 'label' => 'Chờ xác nhận'],
    ];

    public function index(Site $site, Request $request, ListOrderDetails $action): Response
    {
        Gate::authorize('viewAny', [Order::class, $site]);

        $activeFilterStatus = null;
        $filterStatusTransitions = [];
        if ($request->filled('filter_status')) {
            try {
                $fs = OrderStatus::from((int) $request->input('filter_status'));
                $activeFilterStatus = [
                    'value' => $fs->value,
                    'label' => $fs->label(),
                ];
                if (! $fs->isFinal()) {
                    $filterStatusTransitions = collect($fs->transitions())
                        ->map(fn (OrderStatus $s) => [
                            'value' => $s->value,
                            'label' => $s->label(),
                        ])
                        ->values()
                        ->all();
                }
            } catch (\ValueError) {
                $activeFilterStatus = null;
                $filterStatusTransitions = [];
            }
        }

        $orderDetails = $action->execute($site, $request)
            ->through(function (OrderDetail $detail) {
                $status = $detail->status instanceof OrderStatus ? $detail->status : OrderStatus::from((int) $detail->status);

                return [
                    'id' => $detail->id,
                    'qty' => (int) $detail->qty,
                    'price' => (float) $detail->price,
                    'discount' => (float) $detail->discount,
                    'total' => (float) $detail->total,
                    'order_date' => $detail->order_date?->format('Y-m-d H:i:s'),
                    'status' => $status->value,
                    'status_label' => $status->label(),
                    'payment_status' => (int) $detail->payment_status,
                    'payment_request_detail_id' => $detail->payment_request_detail_id !== null
                        ? (int) $detail->payment_request_detail_id
                        : null,
                    'order' => [
                        'id' => $detail->order?->id,
                        'order_number' => $detail->order?->order_number,
                    ],
                    'customer' => [
                        'id' => $detail->order?->customer?->id,
                        'name' => $detail->order?->customer?->name,
                    ],
                    'product' => [
                        'id' => $detail->productItem?->product?->id,
                        'name' => $detail->productItem?->product?->name,
                    ],
                    'product_item' => [
                        'id' => $detail->productItem?->id,
                        'name' => $detail->productItem?->name,
                        'sku' => $detail->productItem?->sku,
                    ],
                    'product_type' => [
                        'id' => $detail->productItem?->product?->productType?->id,
                        'name' => $detail->productItem?->product?->productType?->name,
                        'color' => $detail->productItem?->product?->productType?->color,
                    ],
                    'can_update_status' => ! $status->isFinal(),
                    'allowed_status_values' => collect($status->transitions())
                        ->prepend($status)
                        ->map(fn (OrderStatus $allowedStatus) => $allowedStatus->value)
                        ->values(),
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

        return Inertia::render('Orders/Details/Index', [
            'site' => $site,
            'filters' => [
                'search' => $request->string('search')->toString(),
                'customer_id' => $request->string('customer_id')->toString(),
                'product_id' => $request->string('product_id')->toString(),
                'product_item_id' => $request->string('product_item_id')->toString(),
                'product_type_id' => $request->string('product_type_id')->toString(),
                'filter_status' => $request->string('filter_status')->toString(),
                'payment_statuses' => $request->input('payment_statuses', []),
                'date_from' => $request->string('date_from')->toString(),
                'date_to' => $request->string('date_to')->toString(),
            ],
            'activeFilterStatus' => $activeFilterStatus,
            'filterStatusTransitions' => $filterStatusTransitions,
            'orderDetails' => $orderDetails,
            'statusOptions' => OrderStatus::options(),
            'paymentStatusOptions' => self::PAYMENT_STATUS_OPTIONS,
            'filterCustomer' => $filterCustomer,
            'products' => Product::query()->where('site_id', $site->id)->orderBy('name')->get(['id', 'name']),
            'productItems' => ProductItem::query()->where('site_id', $site->id)->orderBy('name')->get(['id', 'name', 'sku', 'product_id']),
            'productTypes' => ProductType::query()->where('site_id', $site->id)->orderBy('name')->get(['id', 'name', 'color']),
        ]);
    }

    public function show(Site $site, OrderDetail $orderDetail): Response
    {
        $orderDetail->loadMissing('order');
        abort_if(! $orderDetail->order, 404);
        Gate::authorize('view', [$orderDetail->order, $site]);

        $orderDetail->load([
            'order.customer',
            'order.shippingAddress.ward.province',
            'productItem.product.productType',
        ]);

        $status = $orderDetail->status instanceof OrderStatus ? $orderDetail->status : OrderStatus::from((int) $orderDetail->status);
        $paymentStatusLabel = collect(self::PAYMENT_STATUS_OPTIONS)
            ->firstWhere('value', (string) (int) $orderDetail->payment_status)['label'] ?? 'Không xác định';

        $statusHistory = collect([
            [
                'title' => 'Tạo chi tiết đơn hàng',
                'status' => $status->label(),
                'note' => $orderDetail->note,
                'at' => $orderDetail->created_at?->format('Y-m-d H:i:s'),
            ],
            [
                'title' => 'Cập nhật gần nhất',
                'status' => $status->label(),
                'note' => $orderDetail->note,
                'at' => $orderDetail->updated_at?->format('Y-m-d H:i:s'),
            ],
        ])->filter(fn ($item) => ! empty($item['at']))->values();

        return Inertia::render('Orders/Details/Show', [
            'site' => $site,
            'orderDetail' => [
                'id' => $orderDetail->id,
                'order' => [
                    'id' => $orderDetail->order?->id,
                    'order_number' => $orderDetail->order?->order_number,
                    'order_date' => $orderDetail->order?->order_date?->format('Y-m-d H:i:s'),
                    'status' => $orderDetail->order?->status instanceof OrderStatus
                        ? $orderDetail->order?->status->label()
                        : ($orderDetail->order ? OrderStatus::from((int) $orderDetail->order->status)->label() : null),
                ],
                'customer' => [
                    'id' => $orderDetail->order?->customer?->id,
                    'name' => $orderDetail->order?->customer?->name,
                    'phone' => $orderDetail->order?->customer?->phone,
                    'email' => $orderDetail->order?->customer?->email,
                ],
                'shipping_address' => $orderDetail->order?->shippingAddress
                    ? [
                        'address' => $orderDetail->order->shippingAddress->address,
                        'ward' => $orderDetail->order->shippingAddress->ward?->name,
                        'province' => $orderDetail->order->shippingAddress->ward?->province?->name,
                    ]
                    : null,
                'product' => [
                    'id' => $orderDetail->productItem?->product?->id,
                    'name' => $orderDetail->productItem?->product?->name,
                    'type' => $orderDetail->productItem?->product?->productType?->name,
                    'type_color' => $orderDetail->productItem?->product?->productType?->color,
                ],
                'product_item' => [
                    'id' => $orderDetail->productItem?->id,
                    'name' => $orderDetail->productItem?->name,
                    'sku' => $orderDetail->productItem?->sku,
                ],
                'pricing' => [
                    'qty' => (int) $orderDetail->qty,
                    'price' => (float) $orderDetail->price,
                    'discount' => (float) $orderDetail->discount,
                    'addition_price' => (float) $orderDetail->addition_price,
                    'total' => (float) $orderDetail->total,
                ],
                'status' => [
                    'value' => $status->value,
                    'label' => $status->label(),
                    'can_update' => ! $status->isFinal(),
                    'allowed_status_values' => collect($status->transitions())
                        ->prepend($status)
                        ->map(fn (OrderStatus $allowedStatus) => $allowedStatus->value)
                        ->values(),
                ],
                'payment_status' => [
                    'value' => (int) $orderDetail->payment_status,
                    'label' => $paymentStatusLabel,
                ],
                'payment_request_detail_id' => $orderDetail->payment_request_detail_id !== null
                    ? (int) $orderDetail->payment_request_detail_id
                    : null,
                'notes' => [
                    'order_detail_note' => $orderDetail->note,
                    'order_note' => $orderDetail->order?->order_note,
                    'shipping_note' => $orderDetail->order?->shipping_note,
                ],
                'status_history' => $statusHistory,
            ],
            'statusOptions' => OrderStatus::options(),
            'paymentStatusOptions' => self::PAYMENT_STATUS_OPTIONS,
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

        $currentStatus = $orderDetail->status instanceof OrderStatus ? $orderDetail->status : OrderStatus::from((int) $orderDetail->status);
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
        $orderStatus = $order->status instanceof OrderStatus ? $order->status : OrderStatus::from((int) $order->status);
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
        $currentPaymentStatus = (int) $orderDetail->payment_status;
        if (! $action->canTransition($currentPaymentStatus, $nextPaymentStatus)) {
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
        UpdateOrderDetailStatus $action
    ): RedirectResponse {
        Gate::authorize('viewAny', [Order::class, $site]);

        $targetStatus = OrderStatus::from((int) $request->integer('status'));
        $note = $request->string('note')->toString() ?: null;

        $successCount = 0;
        $failed = [];

        $ids = array_values(array_filter(array_map('intval', (array) $request->input('order_detail_ids', []))));

        if (count($ids) > 0) {
            $orderDetails = OrderDetail::query()
                ->where('site_id', $site->id)
                ->whereIn('id', $ids)
                ->with('order')
                ->get();
        } else {
            $filterStatus = OrderStatus::from((int) $request->integer('filter_status'));
            $orderDetails = OrderDetail::query()
                ->where('site_id', $site->id)
                ->where('status', $filterStatus->value)
                ->with('order')
                ->get();
        }

        $targetStatusValue = $targetStatus->value;

        foreach ($orderDetails as $detail) {
            $currentStatus = $detail->status instanceof OrderStatus ? $detail->status : OrderStatus::from((int) $detail->status);
            if ($currentStatus->isFinal()) {
                $failed[] = "#{$detail->id}: trạng thái hiện tại là final";

                continue;
            }

            $allowedNextValues = array_map(
                fn (OrderStatus $s) => $s->value,
                $currentStatus->transitions(),
            );
            if (! in_array($targetStatusValue, $allowedNextValues, true)) {
                $failed[] = "#{$detail->id}: transition không hợp lệ";

                continue;
            }

            $order = $detail->order;
            if (! $order) {
                $failed[] = "#{$detail->id}: không tìm thấy đơn hàng";

                continue;
            }

            $orderStatus = $order->status instanceof OrderStatus ? $order->status : OrderStatus::from((int) $order->status);
            if ($orderStatus->isFinal()) {
                $failed[] = "#{$detail->id}: order đã final";

                continue;
            }

            try {
                $action->execute($order, $detail, $targetStatus->value, $note);
                $successCount++;
            } catch (\Exception $e) {
                Log::error($e->getMessage());
                $failed[] = "#{$detail->id}: lỗi hệ thống khi cập nhật";
            }
        }

        if (count($failed) > 0) {
            return back()->with('error', "Bulk update: {$successCount} thành công, ".count($failed).' thất bại. '.implode(' | ', $failed));
        }

        return back()->with('success', "OrderDetails đã được cập nhật thành công: {$successCount}.");
    }
}
