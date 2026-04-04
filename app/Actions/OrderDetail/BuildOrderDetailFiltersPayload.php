<?php

namespace App\Actions\OrderDetail;

use App\Enums\OrderStatus;
use Illuminate\Http\Request;

class BuildOrderDetailFiltersPayload
{
    public function execute(Request $request): array
    {
        $activeFilterStatus = null;
        $filterStatusTransitions = [];

        if ($request->filled('filter_status')) {
            try {
                $status = OrderStatus::from((int) $request->input('filter_status'));
                $activeFilterStatus = [
                    'value' => $status->value,
                    'label' => $status->label(),
                ];

                if (! $status->isFinal()) {
                    $filterStatusTransitions = collect($status->transitions())
                        ->map(fn (OrderStatus $transitionStatus) => [
                            'value' => $transitionStatus->value,
                            'label' => $transitionStatus->label(),
                        ])
                        ->values()
                        ->all();
                }
            } catch (\ValueError) {
                // Invalid filter_status is ignored.
            }
        }

        return [
            'activeFilterStatus' => $activeFilterStatus,
            'filterStatusTransitions' => $filterStatusTransitions,
            'filters' => [
                'search' => $request->string('search')->toString(),
                'customer_id' => $request->string('customer_id')->toString(),
                'product_id' => $request->string('product_id')->toString(),
                'product_item_id' => $request->string('product_item_id')->toString(),
                'product_type_id' => $request->string('product_type_id')->toString(),
                'supplier_id' => $request->string('supplier_id')->toString(),
                'filter_status' => $request->filled('filter_status') ? (string) $request->input('filter_status') : '',
                'payment_statuses' => $request->input('payment_statuses', []),
                'date_from' => $request->string('date_from')->toString(),
                'date_to' => $request->string('date_to')->toString(),
                'per_page' => (int) $request->input('per_page', 50),
            ],
        ];
    }
}
