<?php

use App\Actions\Customer\DeleteCustomer;
use App\Models\Address;
use App\Models\Customer;
use App\Models\Site;
use App\Models\Ward;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(\Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->action = new DeleteCustomer;
    $this->site = Site::factory()->create();

    $province = \App\Models\Province::factory()->create();
    $this->ward = Ward::factory()->create(['province_id' => $province->id]);
});

test('delete customer removes customer and its addresses when no orders', function () {
    $customer = Customer::factory()->forSite($this->site)->create();

    $address = Address::factory()->forCustomer($customer)->forWard($this->ward)->create();

    $this->action->execute($customer);

    $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    $this->assertDatabaseMissing('addresses', ['id' => $address->id]);
});

test('delete customer throws exception when customer has orders', function () {
    $customer = Customer::factory()->forSite($this->site)->create();

    DB::table('orders')->insert([
        'payment_status' => 1,
        'order_number' => 'ORD-UNIT-001',
        'order_date' => now(),
        'customer_type' => 1,
        'status' => 1,
        'shipping_payer' => 1,
        'phone' => '0901111111',
        'shipping_note' => null,
        'order_note' => null,
        'sale_channel' => 1,
        'shipping_address_id' => null,
        'customer_id' => $customer->id,
        'site_id' => $this->site->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(fn () => $this->action->execute($customer))
        ->toThrow(\DomainException::class, 'Không thể xóa khách hàng đã có đơn hàng.');

    $this->assertDatabaseHas('customers', ['id' => $customer->id]);
});
