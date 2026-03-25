<?php

use App\Enums\CustomerType;
use App\Models\Address;
use App\Models\Customer;
use App\Models\Province;
use App\Models\Site;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('belongs to a site', function (): void {
    $site = Site::factory()->create();
    $customer = Customer::factory()->forSite($site)->create();

    expect($customer->site)->not->toBeNull()
        ->and($customer->site->id)->toBe($site->id);
});

it('has one user relationship', function (): void {
    $customer = Customer::factory()->create();
    $user = User::factory()->forCustomer($customer)->create();

    expect($customer->user)->not->toBeNull()
        ->and($customer->user->id)->toBe($user->id);
});

it('scopes customers by site', function (): void {
    $siteA = Site::factory()->create();
    $siteB = Site::factory()->create();

    $customerA = Customer::factory()->forSite($siteA)->create();
    Customer::factory()->forSite($siteB)->create();

    $results = Customer::forSite($siteA->id)->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()?->id)->toBe($customerA->id);
});

it('casts type to customer type enum', function (): void {
    $customer = Customer::factory()->create([
        'type' => CustomerType::BUSINESS->value,
    ]);

    expect($customer->type)->toBe(CustomerType::BUSINESS);
});

it('returns default address via accessor', function (): void {
    $province = Province::factory()->create();
    $ward = Ward::factory()->create(['province_id' => $province->id]);

    $customer = Customer::factory()->create();

    Address::factory()->forCustomer($customer)->forWard($ward)->create([
        'address' => '123 Test Street',
        'is_default' => true,
    ]);

    $customer->refresh()->load('addresses.ward.province');

    expect($customer->address)->toContain('123 Test Street')
        ->and($customer->address)->toContain($ward->name);
});
