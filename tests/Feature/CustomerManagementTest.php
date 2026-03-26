<?php

use App\Enums\CustomerType;
use App\Models\Address;
use App\Models\Customer;
use App\Models\Province;
use App\Models\Site;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::findOrCreate('manage_customers', 'web');
    Permission::findOrCreate('view_customers', 'web');
    Permission::findOrCreate('create_customers', 'web');
    Permission::findOrCreate('edit_customers', 'web');
    Permission::findOrCreate('delete_customers', 'web');

    $this->user = User::factory()->create();
    $this->site = Site::factory()->create(['user_id' => $this->user->id]);

    $this->user->givePermissionTo([
        'manage_customers',
        'view_customers',
        'create_customers',
        'edit_customers',
        'delete_customers',
    ]);

    $province = Province::factory()->create();
    $this->ward1 = Ward::factory()->create(['province_id' => $province->id]);
    $this->ward2 = Ward::factory()->create(['province_id' => $province->id]);
});

test('customer index returns correct statistics for individual and business', function () {
    Customer::factory()->forSite($this->site)->individual()->create();
    Customer::factory()->forSite($this->site)->business()->create();

    $response = $this->actingAs($this->user)
        ->get(route('site.customers.index', $this->site->slug));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('site/customers/Index')
            ->where('statistics.total', 2)
            ->where('statistics.individual', 1)
            ->where('statistics.business', 1)
            ->missing('statistics.corporate')
        );
});

test('user cannot view customers of another site', function () {
    $otherSite = Site::factory()->create();

    $response = $this->actingAs($this->user)
        ->get(route('site.customers.index', $otherSite->slug));

    $response->assertForbidden();
});

test('can create business customer with multiple addresses', function () {
    $payload = [
        'name' => 'Business Customer',
        'phone' => '0901234567',
        'email' => 'business.customer@example.com',
        'type' => CustomerType::BUSINESS->value,
        'description' => 'Business note',
        'addresses' => [
            [
                'address' => 'Address 1',
                'ward_id' => $this->ward1->id,
                'is_default' => true,
            ],
            [
                'address' => 'Address 2',
                'ward_id' => $this->ward2->id,
                'is_default' => false,
            ],
        ],
    ];

    $response = $this->actingAs($this->user)
        ->post(route('site.customers.store', $this->site->slug), $payload);

    $response->assertRedirect(route('site.customers.index', $this->site->slug))
        ->assertSessionHas('success');

    $customer = Customer::query()->where('email', 'business.customer@example.com')->first();

    expect($customer)->not->toBeNull();
    $typeValue = $customer->type instanceof CustomerType ? $customer->type->value : (int) $customer->type;

    expect($typeValue)->toBe(CustomerType::BUSINESS->value)
        ->and($customer->addresses()->count())->toBe(2)
        ->and($customer->addresses()->where('is_default', 1)->count())->toBe(1);
});

test('can update customer to individual and keep only one address', function () {
    $customer = Customer::factory()->forSite($this->site)->business()->create([
        'email' => 'old.customer@example.com',
    ]);

    Address::factory()->forCustomer($customer)->forWard($this->ward1)->create(['is_default' => 1]);
    Address::factory()->forCustomer($customer)->forWard($this->ward2)->create(['is_default' => 0]);

    $payload = [
        'name' => 'Updated Individual',
        'phone' => '0912345678',
        'email' => 'updated.customer@example.com',
        'type' => CustomerType::INDIVIDUAL->value,
        'description' => 'Updated description',
        'addresses' => [
            [
                'id' => $customer->addresses()->first()->id,
                'address' => 'Only Address',
                'ward_id' => $this->ward1->id,
                'is_default' => true,
            ],
        ],
    ];

    $response = $this->actingAs($this->user)
        ->put(route('site.customers.update', [$this->site->slug, $customer->id]), $payload);

    $response->assertRedirect(route('site.customers.index', $this->site->slug))
        ->assertSessionHas('success');

    $customer->refresh();

    $typeValue = $customer->type instanceof CustomerType ? $customer->type->value : (int) $customer->type;

    expect($typeValue)->toBe(CustomerType::INDIVIDUAL->value)
        ->and($customer->addresses()->count())->toBe(1)
        ->and($customer->addresses()->first()->address)->toBe('Only Address')
        ->and($customer->addresses()->first()->is_default)->toBeTrue();
});

test('validation fails when individual customer has multiple addresses', function () {
    $payload = [
        'name' => 'Invalid Individual',
        'phone' => '0909999999',
        'email' => 'invalid.individual@example.com',
        'type' => CustomerType::INDIVIDUAL->value,
        'addresses' => [
            ['address' => 'Address 1', 'ward_id' => $this->ward1->id, 'is_default' => true],
            ['address' => 'Address 2', 'ward_id' => $this->ward2->id, 'is_default' => false],
        ],
    ];

    $response = $this->actingAs($this->user)
        ->from(route('site.customers.create', $this->site->slug))
        ->post(route('site.customers.store', $this->site->slug), $payload);

    $response->assertRedirect(route('site.customers.create', $this->site->slug));
    $response->assertSessionHasErrors('addresses');
});

test('can search customers by address through index endpoint', function () {
    $provinceCanTho = Province::factory()->create(['name' => 'Thành phố Cần Thơ']);
    $provinceHcm = Province::factory()->create(['name' => 'Thành phố Hồ Chí Minh']);

    $wardNinhKieu = Ward::factory()->create([
        'name' => 'Phường Ninh Kiều',
        'province_id' => $provinceCanTho->id,
    ]);
    $wardTanDinh = Ward::factory()->create([
        'name' => 'Phường Tân Định',
        'province_id' => $provinceHcm->id,
    ]);

    $customerCanTho = Customer::factory()->forSite($this->site)->create([
        'name' => 'Khách Cần Thơ',
        'email' => 'cantho.index@example.com',
    ]);
    Address::factory()->forCustomer($customerCanTho)->forWard($wardNinhKieu)->create([
        'address' => '123 Nguyễn Trãi',
    ]);

    $customerHcm = Customer::factory()->forSite($this->site)->create([
        'name' => 'Khách Hồ Chí Minh',
        'email' => 'hcm.index@example.com',
    ]);
    Address::factory()->forCustomer($customerHcm)->forWard($wardTanDinh)->create([
        'address' => '456 Hai Bà Trưng',
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('site.customers.index', $this->site->slug).'?search=Cần+Thơ');

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('site/customers/Index')
            ->where('customers.total', 1)
            ->where('customers.data.0.email', 'cantho.index@example.com')
            ->where('filters.search', 'Cần Thơ')
        );
});

test('can filter customers by province and ward through index endpoint', function () {
    $provinceCanTho = Province::factory()->create(['name' => 'Thành phố Cần Thơ']);
    $provinceHcm = Province::factory()->create(['name' => 'Thành phố Hồ Chí Minh']);

    $wardNinhKieu = Ward::factory()->create([
        'name' => 'Phường Ninh Kiều',
        'province_id' => $provinceCanTho->id,
    ]);
    $wardTanDinh = Ward::factory()->create([
        'name' => 'Phường Tân Định',
        'province_id' => $provinceHcm->id,
    ]);

    $customerCanTho = Customer::factory()->forSite($this->site)->create([
        'email' => 'cantho.filter.index@example.com',
    ]);
    Address::factory()->forCustomer($customerCanTho)->forWard($wardNinhKieu)->create();

    $customerHcm = Customer::factory()->forSite($this->site)->create([
        'email' => 'hcm.filter.index@example.com',
    ]);
    Address::factory()->forCustomer($customerHcm)->forWard($wardTanDinh)->create();

    $responseByProvince = $this->actingAs($this->user)
        ->get(route('site.customers.index', $this->site->slug).'?province_id='.$provinceCanTho->id);

    $responseByProvince->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('site/customers/Index')
            ->where('customers.total', 1)
            ->where('customers.data.0.email', 'cantho.filter.index@example.com')
            ->where('filters.province_id', (string) $provinceCanTho->id)
        );

    $responseByWard = $this->actingAs($this->user)
        ->get(route('site.customers.index', $this->site->slug).'?ward_id='.$wardNinhKieu->id);

    $responseByWard->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('site/customers/Index')
            ->where('customers.total', 1)
            ->where('customers.data.0.email', 'cantho.filter.index@example.com')
            ->where('filters.ward_id', (string) $wardNinhKieu->id)
        );
});

test('cannot delete customer when customer already has orders', function () {
    $customer = Customer::factory()->forSite($this->site)->create();

    DB::table('orders')->insert([
        'payment_status' => 1,
        'order_number' => 'ORD-CUS-001',
        'order_date' => now(),
        'customer_type' => 1,
        'status' => 1,
        'shipping_payer' => 1,
        'phone' => '0901234567',
        'shipping_note' => null,
        'order_note' => null,
        'sale_channel' => 1,
        'shipping_address_id' => null,
        'customer_id' => $customer->id,
        'site_id' => $this->site->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->actingAs($this->user)
        ->delete(route('site.customers.destroy', [$this->site->slug, $customer->id]));

    $response->assertRedirect(route('site.customers.index', $this->site->slug));
    $response->assertSessionHas('error');
    $this->assertDatabaseHas('customers', ['id' => $customer->id]);
});
