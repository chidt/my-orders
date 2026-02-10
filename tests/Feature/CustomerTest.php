<?php

use App\Enums\CustomerType;
use App\Models\Address;
use App\Models\Customer;
use App\Models\Province;
use App\Models\Site;
use App\Models\User;
use App\Models\Ward;

// Setup test data for factories that need existing provinces/wards
beforeEach(function () {
    // Create test provinces if needed
    if (Province::count() === 0) {
        Province::insert([
            ['name' => 'Test Province 1', 'gso_id' => 'TP1', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Test Province 2', 'gso_id' => 'TP2', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    // Create test wards if needed
    if (Ward::count() === 0) {
        $provinces = Province::all();
        foreach ($provinces as $province) {
            Ward::insert([
                'name' => 'Test Ward for '.$province->name,
                'gso_id' => 'TW'.$province->id,
                'province_id' => $province->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
});

test('customer can be created with factory', function () {
    $customer = Customer::factory()->create();

    expect($customer)->toBeInstanceOf(Customer::class);
    expect($customer->name)->not->toBeEmpty();
    expect($customer->email)->not->toBeEmpty();
    expect($customer->phone)->not->toBeEmpty();
});

test('customer has relationships', function () {
    $customer = Customer::factory()->create();

    // Create user for the customer manually
    $user = User::factory()->forCustomer($customer)->create();

    // Create addresses for the customer manually
    Address::factory(2)->forCustomer($customer)->create();

    // Refresh customer to load relationships
    $customer = Customer::with(['user', 'addresses', 'site'])->find($customer->id);

    // Test User relationship
    expect($customer->user)->toBeInstanceOf(User::class);
    expect($customer->user->customer_id)->toBe($customer->id);

    // Test Addresses relationship
    expect($customer->addresses)->toHaveCount(2);
    expect($customer->addresses->first())->toBeInstanceOf(Address::class);

    // Test Site relationship
    expect($customer->site)->toBeInstanceOf(Site::class);
});

test('customer type enum works correctly', function () {
    $customer = Customer::factory()->create([
        'type' => CustomerType::BUSINESS->value,
    ]);

    expect($customer->type)->toBe(CustomerType::BUSINESS->value);
    expect(CustomerType::from($customer->type))->toBe(CustomerType::BUSINESS);
});

test('address has relationships', function () {
    $customer = Customer::factory()->create();
    $ward = Ward::first();

    $address = Address::factory()
        ->forCustomer($customer)
        ->forWard($ward)
        ->create();

    $address = Address::with(['addressable', 'ward.province'])->find($address->id);

    expect($address->addressable)->toBeInstanceOf(Customer::class);
    expect($address->ward)->toBeInstanceOf(Ward::class);
    expect($address->ward->province)->toBeInstanceOf(Province::class);
    expect($address->addressable_id)->toBe($customer->id);
    expect($address->addressable_type)->toBe(Customer::class);
    expect($address->ward_id)->toBe($ward->id);
});

test('user has customer relationship', function () {
    $customer = Customer::factory()->create();
    $user = User::factory()->forCustomer($customer)->create();

    expect($user->customer)->toBeInstanceOf(Customer::class);
    expect($user->customer->id)->toBe($customer->id);
    expect($user->customer_id)->toBe($customer->id);
});

test('customer email is unique', function () {
    $customer1 = Customer::factory()->create();

    expect(fn () => Customer::factory()->create(['email' => $customer1->email]))
        ->toThrow('Illuminate\Database\QueryException');
});

test('customer can have multiple addresses', function () {
    $customer = Customer::factory()->create();

    $address1 = Address::factory()->forCustomer($customer)->create();
    $address2 = Address::factory()->forCustomer($customer)->create();

    $customer = Customer::with('addresses')->find($customer->id);
    expect($customer->addresses)->toHaveCount(2);
    expect($customer->addresses->pluck('id'))->toContain($address1->id, $address2->id);
});

test('customer factory creates site when none exists', function () {
    // Ensure no sites exist
    Site::query()->delete();

    $customer = Customer::factory()->create();

    expect($customer->site_id)->not->toBeNull();
    expect($customer->site)->toBeInstanceOf(Site::class);
});

test('user factory handles customer assignment correctly', function () {
    $customer = Customer::factory()->create();

    // Test with existing customer
    $user = User::factory()->forCustomer($customer)->create();
    expect($user->customer_id)->toBe($customer->id);

    // Test standalone user
    $standaloneUser = User::factory()->withoutCustomer()->create();
    expect($standaloneUser->customer_id)->toBeNull();
});

test('address factory throws error when no wards exist', function () {
    // Remove all wards
    Ward::query()->delete();

    expect(fn () => Address::factory()->create())
        ->toThrow('RuntimeException', 'No existing wards found');
});

test('address factory handles invalid ward id', function () {
    $nonExistentId = 99999;

    expect(fn () => Address::factory()->withWardId($nonExistentId)->create())
        ->toThrow('InvalidArgumentException', "Ward with ID {$nonExistentId} does not exist.");
});

test('customer factory methods work correctly', function () {
    $site = Site::factory()->create();

    // Test forSite method
    $customer1 = Customer::factory()->forSite($site)->create();
    expect($customer1->site_id)->toBe($site->id);

    // Test ofType methods
    $individualCustomer = Customer::factory()->individual()->create();
    expect($individualCustomer->type)->toBe(CustomerType::INDIVIDUAL->value);

    $businessCustomer = Customer::factory()->business()->create();
    expect($businessCustomer->type)->toBe(CustomerType::BUSINESS->value);

    $corporateCustomer = Customer::factory()->corporate()->create();
    expect($corporateCustomer->type)->toBe(CustomerType::CORPORATE->value);
});
