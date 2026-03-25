<?php

use App\Actions\Customer\UpdateCustomer;
use App\Enums\CustomerType;
use App\Models\Address;
use App\Models\Customer;
use App\Models\Province;
use App\Models\Site;
use App\Models\Ward;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(\Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->action = new UpdateCustomer;
    $this->site = Site::factory()->create();

    $province = Province::factory()->create();
    $this->ward1 = Ward::factory()->create(['province_id' => $province->id]);
    $this->ward2 = Ward::factory()->create(['province_id' => $province->id]);
});

test('update customer to individual keeps only one default address', function () {
    $customer = Customer::factory()->forSite($this->site)->business()->create();

    Address::factory()->forCustomer($customer)->forWard($this->ward1)->create([
        'address' => 'Old Address 1',
        'is_default' => 1,
    ]);

    Address::factory()->forCustomer($customer)->forWard($this->ward2)->create([
        'address' => 'Old Address 2',
        'is_default' => 0,
    ]);

    $updated = $this->action->execute($customer, [
        'name' => 'Updated Individual',
        'phone' => '0911111111',
        'email' => 'updated.individual.unit@example.com',
        'type' => CustomerType::INDIVIDUAL->value,
        'addresses' => [
            [
                'address' => 'New Single Address',
                'ward_id' => $this->ward2->id,
                'is_default' => 0,
            ],
        ],
    ]);

    $updated->refresh()->load('addresses');

    expect($updated->type)->toBe(CustomerType::INDIVIDUAL);
    expect($updated->addresses)->toHaveCount(1);
    expect($updated->addresses->first()->address)->toBe('New Single Address');
    expect($updated->addresses->first()->is_default)->toBeTrue();
});

test('update customer business reassigns single default among multiple addresses', function () {
    $customer = Customer::factory()->forSite($this->site)->business()->create();

    $address1 = Address::factory()->forCustomer($customer)->forWard($this->ward1)->create([
        'address' => 'Address 1',
        'is_default' => 1,
    ]);

    $address2 = Address::factory()->forCustomer($customer)->forWard($this->ward2)->create([
        'address' => 'Address 2',
        'is_default' => 0,
    ]);

    $updated = $this->action->execute($customer, [
        'name' => 'Updated Business',
        'phone' => '0922222222',
        'email' => 'updated.business.unit@example.com',
        'type' => CustomerType::BUSINESS->value,
        'addresses' => [
            [
                'id' => $address1->id,
                'address' => 'Address 1 Updated',
                'ward_id' => $this->ward1->id,
                'is_default' => 0,
            ],
            [
                'id' => $address2->id,
                'address' => 'Address 2 Updated',
                'ward_id' => $this->ward2->id,
                'is_default' => 1,
            ],
        ],
    ]);

    $updated->refresh()->load('addresses');

    expect($updated->type)->toBe(CustomerType::BUSINESS)
        ->and($updated->addresses)->toHaveCount(2)
        ->and($updated->addresses->where('is_default', true))->toHaveCount(1)
        ->and($updated->addresses->firstWhere('is_default', true)?->address)->toBe('Address 2 Updated');
});
