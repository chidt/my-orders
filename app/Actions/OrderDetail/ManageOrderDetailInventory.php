<?php

namespace App\Actions\OrderDetail;

use App\Models\Location;
use App\Models\OrderDetail;
use App\Models\WarehouseInventory;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class ManageOrderDetailInventory
{
    public function getAvailableQty(int $siteId, int $productItemId): int
    {
        return (int) WarehouseInventory::query()
            ->where('site_id', $siteId)
            ->where('product_item_id', $productItemId)
            ->selectRaw('COALESCE(SUM(current_qty - reserved_qty), 0) as available_qty')
            ->value('available_qty');
    }

    public function reserveForDetail(OrderDetail $detail): bool
    {
        $availableQty = $this->getAvailableQty($detail->site_id, $detail->product_item_id);
        if ($availableQty < $detail->qty) {
            return false;
        }

        $remaining = (int) $detail->qty;
        $inventories = $this->getInventoriesForAllocation($detail->site_id, $detail->product_item_id);
        foreach ($inventories as $inventory) {
            if ($remaining <= 0) {
                break;
            }

            $allocatable = max(0, (int) $inventory->current_qty - (int) $inventory->reserved_qty);
            if ($allocatable <= 0) {
                continue;
            }

            $allocated = min($remaining, $allocatable);
            $inventory->reserved_qty += $allocated;
            $inventory->last_updated = CarbonImmutable::now();
            $inventory->save();
            $remaining -= $allocated;
        }

        return true;
    }

    public function createPreOrder(OrderDetail $detail): void
    {
        $inventories = $this->getInventoriesForAllocation($detail->site_id, $detail->product_item_id);
        $inventory = $inventories->first();

        if (! $inventory) {
            $locationId = $this->resolveFallbackLocationId($detail->site_id);
            if (! $locationId) {
                return;
            }

            $inventory = WarehouseInventory::query()->firstOrCreate(
                [
                    'product_item_id' => $detail->product_item_id,
                    'location_id' => $locationId,
                    'site_id' => $detail->site_id,
                ],
                [
                    'current_qty' => 0,
                    'reserved_qty' => 0,
                    'pre_order_qty' => 0,
                    'last_updated' => CarbonImmutable::now(),
                ],
            );
        }

        $inventory->pre_order_qty += (int) $detail->qty;
        $inventory->last_updated = CarbonImmutable::now();
        $inventory->save();
    }

    public function releasePreOrder(OrderDetail $detail): void
    {
        $remaining = (int) $detail->qty;
        $inventories = $this->getInventoriesForAllocation($detail->site_id, $detail->product_item_id);

        foreach ($inventories as $inventory) {
            if ($remaining <= 0) {
                break;
            }

            $preOrderQty = max(0, (int) $inventory->pre_order_qty);
            if ($preOrderQty <= 0) {
                continue;
            }

            $released = min($remaining, $preOrderQty);
            $inventory->pre_order_qty -= $released;
            $inventory->last_updated = CarbonImmutable::now();
            $inventory->save();
            $remaining -= $released;
        }
    }

    public function releaseReserved(OrderDetail $detail): void
    {
        $remaining = (int) $detail->qty;
        $inventories = $this->getInventoriesForAllocation($detail->site_id, $detail->product_item_id);
        foreach ($inventories as $inventory) {
            if ($remaining <= 0) {
                break;
            }

            $releasable = max(0, (int) $inventory->reserved_qty);
            if ($releasable <= 0) {
                continue;
            }

            $released = min($remaining, $releasable);
            $inventory->reserved_qty -= $released;
            $inventory->last_updated = CarbonImmutable::now();
            $inventory->save();
            $remaining -= $released;
        }
    }

    public function deductStock(OrderDetail $detail): void
    {
        $remaining = (int) $detail->qty;
        $inventories = $this->getInventoriesForAllocation($detail->site_id, $detail->product_item_id);
        foreach ($inventories as $inventory) {
            if ($remaining <= 0) {
                break;
            }

            $currentQty = max(0, (int) $inventory->current_qty);
            if ($currentQty <= 0) {
                continue;
            }

            $deducted = min($remaining, $currentQty);
            $inventory->current_qty -= $deducted;

            $releaseFromReserved = min($deducted, max(0, (int) $inventory->reserved_qty));
            $inventory->reserved_qty -= $releaseFromReserved;

            $inventory->last_updated = CarbonImmutable::now();
            $inventory->save();
            $remaining -= $deducted;
        }
    }

    public function returnStock(OrderDetail $detail): void
    {
        $inventory = $this->getInventoriesForAllocation($detail->site_id, $detail->product_item_id)->first();
        if (! $inventory) {
            $locationId = $this->resolveFallbackLocationId($detail->site_id);
            if (! $locationId) {
                return;
            }

            $inventory = WarehouseInventory::query()->firstOrCreate(
                [
                    'product_item_id' => $detail->product_item_id,
                    'location_id' => $locationId,
                    'site_id' => $detail->site_id,
                ],
                [
                    'current_qty' => 0,
                    'reserved_qty' => 0,
                    'pre_order_qty' => 0,
                    'last_updated' => CarbonImmutable::now(),
                ],
            );
        }

        $inventory->current_qty += (int) $detail->qty;
        $inventory->last_updated = CarbonImmutable::now();
        $inventory->save();
    }

    /**
     * @return Collection<int, WarehouseInventory>
     */
    private function getInventoriesForAllocation(int $siteId, int $productItemId): Collection
    {
        return WarehouseInventory::query()
            ->where('site_id', $siteId)
            ->where('product_item_id', $productItemId)
            ->orderByDesc('current_qty')
            ->get();
    }

    private function resolveFallbackLocationId(int $siteId): ?int
    {
        return Location::query()
            ->whereHas('warehouse', fn ($query) => $query->where('site_id', $siteId))
            ->orderByDesc('is_default')
            ->value('id');
    }
}
