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
    $this->seed(\Database\Seeders\ProductPermissionSeeder::class);

    $this->user = User::factory()->create();
    $this->site = Site::factory()->create(['user_id' => $this->user->id]);
    $this->user->update(['site_id' => $this->site->id]);
    $this->user->assignRole('SiteAdmin');

    $this->warehouse = Warehouse::factory()->for($this->site)->create();
    $this->location = Location::factory()->for($this->warehouse)->create(['is_default' => true]);

    $this->productType = ProductType::factory()->create(['site_id' => $this->site->id]);
    $this->supplier = Supplier::factory()->forSite($this->site)->create();
    $this->category = Category::factory()->forSite($this->site)->create();
    $this->unit = Unit::factory()->create();
    $this->product = Product::factory()->create([
        'site_id' => $this->site->id,
        'product_type_id' => $this->productType->id,
        'supplier_id' => $this->supplier->id,
        'category_id' => $this->category->id,
        'unit_id' => $this->unit->id,
        'default_location_id' => $this->location->id,
    ]);
    $this->productItem = ProductItem::factory()->forProduct($this->product)->create([
        'site_id' => $this->site->id,
        'price' => 100000,
    ]);

    $this->customer = Customer::factory()->forSite($this->site)->create();
    $this->province = Province::factory()->create();
    $this->ward = Ward::factory()->create(['province_id' => $this->province->id]);
    $this->address = Address::factory()->forCustomer($this->customer)->forWard($this->ward)->create([
        'is_default' => true,
    ]);
});

test('store order keeps new status and does not reserve stock', function () {
    WarehouseInventory::query()->create([
        'site_id' => $this->site->id,
        'product_item_id' => $this->productItem->id,
        'location_id' => $this->location->id,
        'current_qty' => 10,
        'reserved_qty' => 0,
        'pre_order_qty' => 0,
        'last_updated' => now(),
    ]);

    $response = $this->actingAs($this->user)->post(route('orders.store', $this->site), [
        'customer_id' => $this->customer->id,
        'shipping_address_id' => $this->address->id,
        'sale_channel' => 1,
        'shipping_payer' => 1,
        'details' => [
            [
                'product_item_id' => $this->productItem->id,
                'qty' => 3,
            ],
        ],
    ]);

    $response->assertRedirect(route('orders.index', $this->site))
        ->assertSessionHas('success');

    $order = Order::query()->firstOrFail();
    $detail = OrderDetail::query()->firstOrFail();
    $inventory = WarehouseInventory::query()->firstOrFail();

    expect($order->status->value)->toBe(1)
        ->and($detail->status->value)->toBe(1)
        ->and($inventory->reserved_qty)->toBe(0)
        ->and($inventory->pre_order_qty)->toBe(0);
});

test('store order does not create pre-order inventory even when stock is insufficient', function () {
    WarehouseInventory::query()->create([
        'site_id' => $this->site->id,
        'product_item_id' => $this->productItem->id,
        'location_id' => $this->location->id,
        'current_qty' => 1,
        'reserved_qty' => 0,
        'pre_order_qty' => 0,
        'last_updated' => now(),
    ]);

    $this->actingAs($this->user)->post(route('orders.store', $this->site), [
        'customer_id' => $this->customer->id,
        'shipping_address_id' => $this->address->id,
        'sale_channel' => 1,
        'shipping_payer' => 1,
        'details' => [
            [
                'product_item_id' => $this->productItem->id,
                'qty' => 5,
            ],
        ],
    ])->assertRedirect(route('orders.index', $this->site));

    $order = Order::query()->firstOrFail();
    $detail = OrderDetail::query()->firstOrFail();
    $inventory = WarehouseInventory::query()->firstOrFail();

    expect($detail->status->value)->toBe(1)
        ->and($inventory->pre_order_qty)->toBe(0);

    $this->actingAs($this->user)->patch(
        route('orders.details.status.update', [$this->site, $order, $detail]),
        ['status' => 12, 'note' => 'Khách hủy'],
    )->assertSessionHas('success');

    $order->refresh();
    $detail->refresh();
    $inventory->refresh();

    expect($detail->status->value)->toBe(12)
        ->and($order->status->value)->toBe(12)
        ->and($inventory->reserved_qty)->toBe(0)
        ->and($inventory->pre_order_qty)->toBe(0);
});

test('can edit and update order fully', function () {
    WarehouseInventory::query()->create([
        'site_id' => $this->site->id,
        'product_item_id' => $this->productItem->id,
        'location_id' => $this->location->id,
        'current_qty' => 10,
        'reserved_qty' => 0,
        'pre_order_qty' => 0,
        'last_updated' => now(),
    ]);

    $this->actingAs($this->user)->post(route('orders.store', $this->site), [
        'customer_id' => $this->customer->id,
        'shipping_address_id' => $this->address->id,
        'sale_channel' => 1,
        'shipping_payer' => 1,
        'details' => [
            [
                'product_item_id' => $this->productItem->id,
                'qty' => 2,
            ],
        ],
    ])->assertRedirect();

    $order = Order::query()->firstOrFail();

    $this->actingAs($this->user)
        ->get(route('orders.edit', [$this->site, $order]))
        ->assertOk();

    $this->actingAs($this->user)->put(route('orders.update', [$this->site, $order]), [
        'customer_id' => $this->customer->id,
        'shipping_address_id' => $this->address->id,
        'sale_channel' => 2,
        'shipping_payer' => 2,
        'shipping_note' => 'Giao giờ hành chính',
        'order_note' => 'Đơn hàng cập nhật',
        'details' => [
            [
                'product_item_id' => $this->productItem->id,
                'qty' => 1,
                'discount' => 1000,
                'addition_price' => 500,
            ],
        ],
    ])->assertSessionHas('success');

    $order->refresh();
    $detail = $order->orderDetails()->firstOrFail();
    expect($order->sale_channel)->toBe(2)
        ->and($order->shipping_payer)->toBe(2)
        ->and($detail->qty)->toBe(1);
});

test('can delete order', function () {
    WarehouseInventory::query()->create([
        'site_id' => $this->site->id,
        'product_item_id' => $this->productItem->id,
        'location_id' => $this->location->id,
        'current_qty' => 10,
        'reserved_qty' => 0,
        'pre_order_qty' => 0,
        'last_updated' => now(),
    ]);

    $this->actingAs($this->user)->post(route('orders.store', $this->site), [
        'customer_id' => $this->customer->id,
        'shipping_address_id' => $this->address->id,
        'sale_channel' => 1,
        'shipping_payer' => 1,
        'details' => [
            [
                'product_item_id' => $this->productItem->id,
                'qty' => 2,
            ],
        ],
    ])->assertRedirect();

    $order = Order::query()->firstOrFail();

    $this->actingAs($this->user)
        ->delete(route('orders.destroy', [$this->site, $order]))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('orders', ['id' => $order->id]);
});

test('can search customers for order form', function () {
    Customer::factory()->forSite($this->site)->create([
        'name' => 'Nguyen Van Search',
        'phone' => '0912345678',
        'email' => 'search@example.com',
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('customers.search', $this->site).'?search=Nguyen');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Nguyen Van Search');
});

test('can search product items for order form', function () {
    $response = $this->actingAs($this->user)
        ->get(route('product-items.search', $this->site).'?search='.$this->productItem->sku);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $this->productItem->id);
});

test('can quick create customer from order form', function () {
    $payload = [
        'name' => 'Khach Moi',
        'phone' => '0909999888',
        'email' => 'khachmoi@example.com',
        'type' => 1,
        'description' => 'Tao nhanh tu order',
        'address' => '12 Nguyen Trai',
        'ward_id' => $this->ward->id,
        'addresses' => [
            [
                'address' => '12 Nguyen Trai',
                'ward_id' => $this->ward->id,
                'is_default' => 1,
            ],
        ],
    ];

    $response = $this->actingAs($this->user)
        ->postJson(route('customers.quick-store', $this->site), $payload);

    $response->assertCreated()
        ->assertJsonPath('customer.name', 'Khach Moi')
        ->assertJsonPath('customer.email', 'khachmoi@example.com');

    $customerId = $response->json('customer.id');

    $this->assertDatabaseHas('customers', [
        'id' => $customerId,
        'site_id' => $this->site->id,
        'name' => 'Khach Moi',
    ]);
    $this->assertDatabaseHas('addresses', [
        'addressable_id' => $customerId,
        'addressable_type' => Customer::class,
        'ward_id' => $this->ward->id,
    ]);
});
