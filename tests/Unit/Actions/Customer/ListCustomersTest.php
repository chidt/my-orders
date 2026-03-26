<?php

use App\Actions\Customer\ListCustomers;
use App\Enums\CustomerType;
use App\Models\Address;
use App\Models\Customer;
use App\Models\Province;
use App\Models\Site;
use App\Models\Ward;
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

test('list customers can search by address ward or province', function () {
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
        'email' => 'cantho@example.com',
    ]);

    Address::factory()->forCustomer($customerCanTho)->forWard($wardNinhKieu)->create([
        'address' => '123 Nguyễn Trãi',
    ]);

    $customerHcm = Customer::factory()->forSite($this->site)->create([
        'name' => 'Khách Hồ Chí Minh',
        'email' => 'hcm@example.com',
    ]);

    Address::factory()->forCustomer($customerHcm)->forWard($wardTanDinh)->create([
        'address' => '456 Hai Bà Trưng',
    ]);

    $resultByAddress = $this->action->execute($this->site, ['search' => 'Nguyễn Trãi']);
    $resultByWard = $this->action->execute($this->site, ['search' => 'Ninh Kiều']);
    $resultByProvince = $this->action->execute($this->site, ['search' => 'Cần Thơ']);

    expect($resultByAddress->total())->toBe(1)
        ->and($resultByAddress->items()[0]->email)->toBe('cantho@example.com')
        ->and($resultByWard->total())->toBe(1)
        ->and($resultByWard->items()[0]->email)->toBe('cantho@example.com')
        ->and($resultByProvince->total())->toBe(1)
        ->and($resultByProvince->items()[0]->email)->toBe('cantho@example.com');
});

test('list customers can filter by province and ward', function () {
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

    $customerCanTho = Customer::factory()->forSite($this->site)->create(['email' => 'cantho.filter@example.com']);
    Address::factory()->forCustomer($customerCanTho)->forWard($wardNinhKieu)->create();

    $customerHcm = Customer::factory()->forSite($this->site)->create(['email' => 'hcm.filter@example.com']);
    Address::factory()->forCustomer($customerHcm)->forWard($wardTanDinh)->create();

    $resultByProvince = $this->action->execute($this->site, ['province_id' => (string) $provinceCanTho->id]);
    $resultByWard = $this->action->execute($this->site, ['ward_id' => (string) $wardNinhKieu->id]);

    expect($resultByProvince->total())->toBe(1)
        ->and($resultByProvince->items()[0]->email)->toBe('cantho.filter@example.com')
        ->and($resultByWard->total())->toBe(1)
        ->and($resultByWard->items()[0]->email)->toBe('cantho.filter@example.com');
});
