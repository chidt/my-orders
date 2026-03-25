<?php

use App\Actions\Customer\StoreCustomer;
use App\Enums\CustomerType;
use App\Models\Customer;
use App\Models\Province;
use App\Models\Site;
use App\Models\Ward;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(\Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->action = new StoreCustomer;
    $this->site = Site::factory()->create();

    $province = Province::factory()->create();
    $this->ward1 = Ward::factory()->create(['province_id' => $province->id]);
    $this->ward2 = Ward::factory()->create(['province_id' => $province->id]);
});

test('store customer creates individual with exactly one default address', function () {
    $customer = $this->action->execute([
        'name' => 'Individual Customer',
        'phone' => '0901234567',
        'email' => 'individual.unit@example.com',
        'type' => CustomerType::INDIVIDUAL->value,
        'addresses' => [
            [
                'address' => 'Address 1',
                'ward_id' => $this->ward1->id,
                'is_default' => 0,
            ],
            [
                'address' => 'Address 2',
                'ward_id' => $this->ward2->id,
                'is_default' => 1,
            ],
        ],
    ], $this->site);

    expect($customer)->toBeInstanceOf(Customer::class)
        ->and($customer->type)->toBe(CustomerType::INDIVIDUAL);

    $customer->refresh()->load('addresses');

    expect($customer->addresses)->toHaveCount(1)
        ->and($customer->addresses->first()->address)->toBe('Address 1')
        ->and($customer->addresses->first()->is_default)->toBeTrue();
});

test('store customer creates business with one default address when none is set', function () {
    $customer = $this->action->execute([
        'name' => 'Business Customer',
        'phone' => '0909999999',
        'email' => 'business.unit@example.com',
        'type' => CustomerType::BUSINESS->value,
        'addresses' => [
            [
                'address' => 'Business Address 1',
                'ward_id' => $this->ward1->id,
                'is_default' => 0,
            ],
            [
                'address' => 'Business Address 2',
                'ward_id' => $this->ward2->id,
                'is_default' => 0,
            ],
        ],
    ], $this->site);

    $customer->refresh()->load('addresses');

    expect($customer->type)->toBe(CustomerType::BUSINESS)
        ->and($customer->addresses)->toHaveCount(2)
        ->and($customer->addresses->where('is_default', true))->toHaveCount(1)
        ->and($customer->addresses->firstWhere('is_default', true)?->address)->toBe('Business Address 1');
});
