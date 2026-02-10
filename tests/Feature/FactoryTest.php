<?php

use App\Enums\CustomerType;
use App\Models\Address;
use App\Models\Customer;
use App\Models\Province;
use App\Models\Site;
use App\Models\User;
use App\Models\Ward;

test('customer factory creates customer with existing site', function () {
    // Ensure there's at least one site
    $site = Site::factory()->create();

    $customer = Customer::factory()->create();

    expect($customer)->toBeInstanceOf(Customer::class);
    expect($customer->name)->not->toBeEmpty();
    expect($customer->email)->not->toBeEmpty();
    expect($customer->phone)->not->toBeEmpty();
    expect($customer->site_id)->not->toBeNull();
    expect($customer->site)->toBeInstanceOf(Site::class);
});

test('customer factory creates new site when none exists', function () {
    // Delete existing sites
    Site::query()->delete();

    $customer = Customer::factory()->create();

    expect($customer->site_id)->not->toBeNull();
    expect(Site::find($customer->site_id))->not->toBeNull();
});

test('customer factory type methods work', function () {
    $individual = Customer::factory()->individual()->create();
    $business = Customer::factory()->business()->create();
    $corporate = Customer::factory()->corporate()->create();

    expect($individual->type)->toBe(CustomerType::INDIVIDUAL->value);
    expect($business->type)->toBe(CustomerType::BUSINESS->value);
    expect($corporate->type)->toBe(CustomerType::CORPORATE->value);
});

test('address factory creates address with existing ward and addressable', function () {
    // Ensure we have provinces and wards
    $province = Province::factory()->create();
    $ward = Ward::factory()->create(['province_id' => $province->id]);
    $customer = Customer::factory()->create();

    $address = Address::factory()->forCustomer($customer)->create();

    expect($address)->toBeInstanceOf(Address::class);
    expect($address->address)->not->toBeEmpty();
    expect($address->addressable_id)->not->toBeNull();
    expect($address->addressable_type)->toBe(Customer::class);
    expect($address->ward_id)->not->toBeNull();
    expect($address->addressable)->toBeInstanceOf(Customer::class);
    expect($address->ward)->toBeInstanceOf(Ward::class);
    expect($address->ward->province->id)->toBe($ward->province_id);
});

test('address factory creates new addressable when none exists', function () {
    // Delete existing customers and users but keep wards
    Customer::query()->delete();
    User::query()->delete();
    $ward = Ward::factory()->create(['province_id' => Province::factory()->create()->id]);

    $address = Address::factory()->create();

    expect($address->addressable_id)->not->toBeNull();
    expect($address->addressable_type)->not->toBeNull();

    // Should find either a customer or user
    $found = false;
    if ($address->addressable_type === Customer::class) {
        $found = Customer::find($address->addressable_id) !== null;
    } else {
        $found = User::find($address->addressable_id) !== null;
    }
    expect($found)->toBeTrue();
});

test('address factory throws exception when no wards exist', function () {
    // Delete all wards
    Ward::query()->delete();

    expect(fn () => Address::factory()->create())
        ->toThrow(\RuntimeException::class, 'No existing wards found');
});

test('address factory forCustomer method works', function () {
    $customer = Customer::factory()->create();
    $ward = Ward::factory()->create(['province_id' => Province::factory()->create()->id]);

    $address = Address::factory()->forCustomer($customer)->create();

    expect($address->addressable_id)->toBe($customer->id);
    expect($address->addressable_type)->toBe(Customer::class);
    expect($address->addressable->id)->toBe($customer->id);
});

test('address factory forUser method works', function () {
    $user = User::factory()->create();
    $ward = Ward::factory()->create(['province_id' => Province::factory()->create()->id]);

    $address = Address::factory()->forUser($user)->create();

    expect($address->addressable_id)->toBe($user->id);
    expect($address->addressable_type)->toBe(User::class);
    expect($address->addressable->id)->toBe($user->id);
});

test('address factory withWardId validates ward exists', function () {
    expect(fn () => Address::factory()->withWardId(999)->create())
        ->toThrow(\InvalidArgumentException::class, 'Ward with ID 999 does not exist');
});

test('user factory creates user with available customer', function () {
    // Create a customer without a user
    Customer::factory()->create();

    $user = User::factory()->create();

    expect($user)->toBeInstanceOf(User::class);
    expect($user->name)->not->toBeEmpty();
    expect($user->email)->not->toBeEmpty();
    expect($user->customer_id)->not->toBeNull();
    expect($user->customer)->toBeInstanceOf(Customer::class);
});

test('user factory respects customer uniqueness', function () {
    // Create customers and assign them all to users
    $customers = Customer::factory(3)->create();
    foreach ($customers as $customer) {
        User::factory()->create(['customer_id' => $customer->id]);
    }

    // Try to create another user - should not assign a customer
    $user = User::factory()->create();

    expect($user->customer_id)->toBeNull();
    expect($user->customer)->toBeNull();
});

test('user factory forCustomer method works', function () {
    $customer = Customer::factory()->create();

    $user = User::factory()->forCustomer($customer)->create();

    expect($user->customer_id)->toBe($customer->id);
    expect($user->customer->id)->toBe($customer->id);
});

test('user factory withoutCustomer method works', function () {
    $user = User::factory()->withoutCustomer()->create();

    expect($user->customer_id)->toBeNull();
});

test('user factory withoutSite method works', function () {
    $user = User::factory()->withoutSite()->create();

    expect($user->site_id)->toBeNull();
});

test('user factory withCustomerId validates customer exists', function () {
    expect(fn () => User::factory()->withCustomerId(999)->create())
        ->toThrow(\InvalidArgumentException::class, 'Customer with ID 999 does not exist');
});

test('user factory withSiteId validates site exists', function () {
    expect(fn () => User::factory()->withSiteId(999)->create())
        ->toThrow(\InvalidArgumentException::class, 'Site with ID 999 does not exist');
});

test('factory chain relationships work together', function () {
    // Ensure we have wards and provinces
    $province = Province::factory()->create();
    $wards = Ward::factory(2)->create(['province_id' => $province->id]);

    // Test creating a customer with multiple addresses and a user
    $customer = Customer::factory()->create();
    $addresses = Address::factory(2)
        ->forCustomer($customer)
        ->create();
    $user = User::factory()->forCustomer($customer)->create();

    expect($customer->addresses)->toHaveCount(2);
    expect($customer->user)->toBeInstanceOf(User::class);
    expect($user->customer->id)->toBe($customer->id);

    foreach ($addresses as $address) {
        expect($address->addressable->id)->toBe($customer->id);
    }
});
