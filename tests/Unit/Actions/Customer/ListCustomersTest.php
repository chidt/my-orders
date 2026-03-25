<?php

use App\Actions\Customer\ListCustomers;
use App\Enums\CustomerType;
use App\Models\Customer;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(\Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->action = new ListCustomers;
    $this->site = Site::factory()->create();
    $this->otherSite = Site::factory()->create();
});

test('list customers only returns customers in current site', function () {
    Customer::factory()->forSite($this->site)->count(2)->create();
    Customer::factory()->forSite($this->otherSite)->count(3)->create();

    $result = $this->action->execute($this->site);

    expect($result->total())->toBe(2);
});

test('list customers can filter by type', function () {
    Customer::factory()->forSite($this->site)->create(['type' => CustomerType::INDIVIDUAL->value]);
    Customer::factory()->forSite($this->site)->create(['type' => CustomerType::BUSINESS->value]);

    $result = $this->action->execute($this->site, ['type' => (string) CustomerType::BUSINESS->value]);

    $firstType = $result->items()[0]->type;
    $firstTypeValue = $firstType instanceof CustomerType ? $firstType->value : (int) $firstType;

    expect($result->total())->toBe(1)
        ->and($firstTypeValue)->toBe(CustomerType::BUSINESS->value);
});

test('list customers ignores invalid type filter', function () {
    Customer::factory()->forSite($this->site)->count(2)->create();

    $result = $this->action->execute($this->site, ['type' => '3']);

    expect($result->total())->toBe(2);
});

test('list customers can search by name email or phone', function () {
    Customer::factory()->forSite($this->site)->create([
        'name' => 'Nguyen Van A',
        'email' => 'a@example.com',
        'phone' => '0901111111',
    ]);
    Customer::factory()->forSite($this->site)->create([
        'name' => 'Tran Thi B',
        'email' => 'b@example.com',
        'phone' => '0902222222',
    ]);

    $result = $this->action->execute($this->site, ['search' => 'Tran Thi B']);

    expect($result->total())->toBe(1)
        ->and($result->items()[0]->email)->toBe('b@example.com');
});
