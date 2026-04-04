<?php

namespace App\Actions\OrderDetail;

use App\Enums\OrderStatus;
use App\Models\OrderDetail;
use App\Models\Site;

class BulkUpdateOrderDetailStatus
{
    public function execute(Site $site, int $targetStatusValue, ?int $filterStatusValue, array $orderDetailIds, ?string $note, UpdateOrderDetailStatus $action): array
    {
        $targetStatus = OrderStatus::from($targetStatusValue);
        $successCount = 0;
        $failed = [];

        $ids = array_values(array_filter(array_map('intval', $orderDetailIds)));

        if (count($ids) > 0) {
            $orderDetails = OrderDetail::query()
                ->where('site_id', $site->id)
                ->whereIn('id', $ids)
                ->with('order')
                ->get();
        } else {
            $filterStatus = OrderStatus::from((int) $filterStatusValue);
            $orderDetails = OrderDetail::query()
                ->where('site_id', $site->id)
                ->where('status', $filterStatus->value)
                ->with('order')
                ->get();
        }

        foreach ($orderDetails as $detail) {
            $currentStatus = $detail->status;
            if ($currentStatus->isFinal()) {
                $failed[] = "#{$detail->id}: trạng thái hiện tại là final";

                continue;
            }

            $allowedNextValues = array_map(fn (OrderStatus $status) => $status->value, $currentStatus->transitions());
            if (! in_array($targetStatus->value, $allowedNextValues, true)) {
                $failed[] = "#{$detail->id}: transition không hợp lệ";

                continue;
            }

            $order = $detail->order;
            if (! $order) {
                $failed[] = "#{$detail->id}: không tìm thấy đơn hàng";

                continue;
            }

            if ($order->status->isFinal()) {
                $failed[] = "#{$detail->id}: order đã final";

                continue;
            }

            try {
                $action->execute($order, $detail, $targetStatus->value, $note);
                $successCount++;
            } catch (\Throwable) {
                $failed[] = "#{$detail->id}: lỗi hệ thống khi cập nhật";
            }
        }

        return [
            'success_count' => $successCount,
            'failed' => $failed,
        ];
    }
}
