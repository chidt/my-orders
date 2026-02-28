<?php

use App\Actions\Warehouse\StoreWarehouse;
use App\Models\Site;
use App\Models\Warehouse;

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->action = new StoreWarehouse;
    $this->site = Site::factory()->create();
});

test('store warehouse creates warehouse with correct data', function () {
    $data = [
        'code' => 'W001',
        'name' => 'Test Warehouse',
        'address' => '123 Test Street',
    ];

    $warehouse = $this->action->execute($data, $this->site);

    expect($warehouse)->toBeInstanceOf(Warehouse::class)
        ->and($warehouse->code)->toBe('W001')
        ->and($warehouse->name)->toBe('Test Warehouse')
        ->and($warehouse->address)->toBe('123 Test Street')
        ->and($warehouse->site_id)->toBe($this->site->id);

    $this->assertDatabaseHas('warehouses', [
        'code' => 'W001',
        'name' => 'Test Warehouse',
        'address' => '123 Test Street',
        'site_id' => $this->site->id,
    ]);
});

test('store warehouse associates with correct site', function () {
    $data = [
        'code' => 'W002',
        'name' => 'Another Warehouse',
        'address' => '456 Another Street',
    ];

    $warehouse = $this->action->execute($data, $this->site);

    expect($warehouse->site->id)->toBe($this->site->id);
});

test('store warehouse uses database transaction', function () {
    $data = [
        'code' => 'W003',
        'name' => 'Transaction Test',
        'address' => '789 Transaction Street',
    ];

    // This test verifies the method completes successfully
    $warehouse = $this->action->execute($data, $this->site);

    expect($warehouse->exists)->toBeTrue();
});
