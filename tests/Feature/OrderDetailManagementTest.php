<?php

use App\Models\Address;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\ProductItem;
use App\Models\ProductType;
use App\Models\Province;
use App\Models\Site;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Ward;
use App\Models\Warehouse;
use App\Models\WarehouseInventory;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $this->user = User::factory()->create();
    $this->site = Site::factory()->create(['user_id' => $this->user->id]);
    $this->user->update(['site_id' => $this->site->id]);
    $this->user->assignRole('SiteAdmin');

    $warehouse = Warehouse::factory()->for($this->site)->create();
    $location = Location::factory()->for($warehouse)->create(['is_default' => true]);

    $productType = ProductType::factory()->create(['site_id' => $this->site->id]);
    $supplier = Supplier::factory()->forSite($this->site)->create();
    $category = Category::factory()->forSite($this->site)->create();
    $unit = Unit::factory()->create();
    $product = Product::factory()->create([
        'site_id' => $this->site->id,
        'product_type_id' => $productType->id,
        'supplier_id' => $supplier->id,
        'category_id' => $category->id,
        'unit_id' => $unit->id,
        'default_location_id' => $location->id,
    ]);
    $this->productItem = ProductItem::factory()->forProduct($product)->create([
        'site_id' => $this->site->id,
        'price' => 100000,
    ]);

    $customer = Customer::factory()->forSite($this->site)->create(['name' => 'Test Customer']);
    $province = Province::factory()->create();
    $ward = Ward::factory()->create(['province_id' => $province->id]);
    $address = Address::factory()->forCustomer($customer)->forWard($ward)->create(['is_default' => true]);

    $this->order = Order::factory()->create([
        'site_id' => $this->site->id,
        'customer_id' => $customer->id,
        'shipping_address_id' => $address->id,
        'status' => 5,
    ]);

    $this->orderDetail = OrderDetail::factory()->create([
        'order_id' => $this->order->id,
        'site_id' => $this->site->id,
        'product_item_id' => $this->productItem->id,
        'status' => 5,
        'payment_status' => 1,
        'qty' => 2,
    ]);

    WarehouseInventory::query()->create([
        'site_id' => $this->site->id,
        'product_item_id' => $this->productItem->id,
        'location_id' => $location->id,
        'current_qty' => 10,
        'reserved_qty' => 2,
        'pre_order_qty' => 0,
        'last_updated' => now(),
    ]);
});

test('site admin can view order detail list without loading rows until status filter', function () {
    $this->actingAs($this->user)
        ->get(route('order-details.index', $this->site))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Orders/Details/Index')
            ->has('orderDetails.data', 0)
        );
});

test('site admin can load order detail list when filter_status is set', function () {
    $this->actingAs($this->user)
        ->get(route('order-details.index', $this->site).'?filter_status=5')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Orders/Details/Index')
            ->has('orderDetails.data', 1)
            ->where('orderDetails.data.0.id', $this->orderDetail->id)
            ->where('activeFilterStatus.value', 5)
            ->has('filterStatusTransitions')
        );
});

test('site admin can view order detail show page', function () {
    $this->actingAs($this->user)
        ->get(route('order-details.show', [$this->site, $this->orderDetail]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Orders/Details/Show')
            ->where('orderDetail.id', $this->orderDetail->id)
            ->where('orderDetail.order.id', $this->order->id)
        );
});

test('can update order detail payment status with valid transition', function () {
    $this->actingAs($this->user)
        ->patch(route('order-details.payment-status.update', [$this->site, $this->orderDetail]), [
            'payment_status' => 2,
        ])
        ->assertSessionHas('success');

    $this->orderDetail->refresh();
    expect((int) $this->orderDetail->payment_status)->toBe(2);
});

test('cannot update order detail payment status with invalid transition', function () {
    $this->actingAs($this->user)
        ->patch(route('order-details.payment-status.update', [$this->site, $this->orderDetail]), [
            'payment_status' => 5,
        ])
        ->assertSessionHas('error');

    $this->orderDetail->refresh();
    expect((int) $this->orderDetail->payment_status)->toBe(1);
});

test('can bulk update order detail status by ids', function () {
    $this->actingAs($this->user)
        ->patch(route('order-details.bulk-status.update', $this->site), [
            'order_detail_ids' => [$this->orderDetail->id],
            'status' => 6,
            'note' => 'Bulk chuyển pre-order',
        ])
        ->assertSessionHas('success');

    $this->orderDetail->refresh();
    expect((int) $this->orderDetail->status->value)->toBe(6);
});

test('can bulk update order detail status for all rows matching filter_status', function () {
    $this->actingAs($this->user)
        ->patch(route('order-details.bulk-status.update', $this->site), [
            'filter_status' => 5,
            'status' => 6,
        ])
        ->assertSessionHas('success');

    $this->orderDetail->refresh();
    expect((int) $this->orderDetail->status->value)->toBe(6);
});

test('updating to closing order auto updates to ordered when stock is enough', function () {
    $processingDetail = OrderDetail::factory()->create([
        'order_id' => $this->order->id,
        'site_id' => $this->site->id,
        'product_item_id' => $this->productItem->id,
        'status' => 2,
        'payment_status' => 1,
        'qty' => 1,
    ]);

    $inventoryBefore = WarehouseInventory::query()
        ->where('site_id', $this->site->id)
        ->where('product_item_id', $this->productItem->id)
        ->firstOrFail();

    $this->actingAs($this->user)
        ->patch(route('order-details.status.update', [$this->site, $processingDetail]), [
            'status' => 3,
            'note' => 'Chốt đơn',
        ])
        ->assertSessionHas('success');

    $processingDetail->refresh();
    $inventoryBefore->refresh();

    expect((int) $processingDetail->status->value)->toBe(5)
        ->and((int) $inventoryBefore->reserved_qty)->toBe(3);
});

test('updating to closing order auto updates to pre-order and creates inventory when needed', function () {
    WarehouseInventory::query()->where('site_id', $this->site->id)->delete();

    $processingDetail = OrderDetail::factory()->create([
        'order_id' => $this->order->id,
        'site_id' => $this->site->id,
        'product_item_id' => $this->productItem->id,
        'status' => 2,
        'payment_status' => 1,
        'qty' => 5,
    ]);

    $this->actingAs($this->user)
        ->patch(route('order-details.status.update', [$this->site, $processingDetail]), [
            'status' => 3,
            'note' => 'Chốt đơn',
        ])
        ->assertSessionHas('success');

    $processingDetail->refresh();
    $inventory = WarehouseInventory::query()
        ->where('site_id', $this->site->id)
        ->where('product_item_id', $this->productItem->id)
        ->first();

    expect((int) $processingDetail->status->value)->toBe(6)
        ->and($inventory)->not->toBeNull()
        ->and((int) $inventory->pre_order_qty)->toBe(5);
});
