<?php

use App\Actions\Warehouse\UpdateWarehouse;
use App\Models\Site;
use App\Models\Warehouse;

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->action = new UpdateWarehouse;
    $this->site = Site::factory()->create();
    $this->warehouse = Warehouse::factory()->forSite($this->site)->create([
        'code' => 'W001',
        'name' => 'Original Warehouse',
        'address' => '123 Original Street',
    ]);
});

test('update warehouse modifies warehouse with correct data', function () {
    $data = [
        'code' => 'W999',
        'name' => 'Updated Warehouse',
        'address' => '456 Updated Street',
    ];

    $updatedWarehouse = $this->action->execute($this->warehouse, $data);

    expect($updatedWarehouse)->toBeInstanceOf(Warehouse::class)
        ->and($updatedWarehouse->code)->toBe('W999')
        ->and($updatedWarehouse->name)->toBe('Updated Warehouse')
        ->and($updatedWarehouse->address)->toBe('456 Updated Street')
        ->and($updatedWarehouse->id)->toBe($this->warehouse->id);

    $this->assertDatabaseHas('warehouses', [
        'id' => $this->warehouse->id,
        'code' => 'W999',
        'name' => 'Updated Warehouse',
        'address' => '456 Updated Street',
    ]);
});

test('update warehouse returns fresh model', function () {
    $data = [
        'code' => 'W888',
        'name' => 'Fresh Test',
        'address' => '789 Fresh Street',
    ];

    $updatedWarehouse = $this->action->execute($this->warehouse, $data);

    // The returned model should have updated values
    expect($updatedWarehouse->name)->toBe('Fresh Test')
        ->and($updatedWarehouse->wasRecentlyCreated)->toBeFalse();
});

test('update warehouse preserves unchanged fields', function () {
    $originalSiteId = $this->warehouse->site_id;
    $originalCreatedAt = $this->warehouse->created_at;

    $data = [
        'code' => 'W777',
        'name' => 'Preserve Test',
        'address' => '999 Preserve Street',
    ];

    $updatedWarehouse = $this->action->execute($this->warehouse, $data);

    expect($updatedWarehouse->site_id)->toBe($originalSiteId)
        ->and($updatedWarehouse->created_at->equalTo($originalCreatedAt))->toBeTrue();
});

test('update warehouse uses database transaction', function () {
    $data = [
        'code' => 'W666',
        'name' => 'Transaction Update',
        'address' => '111 Transaction Street',
    ];

    $updatedWarehouse = $this->action->execute($this->warehouse, $data);

    expect($updatedWarehouse->code)->toBe('W666');

    $this->assertDatabaseHas('warehouses', [
        'id' => $this->warehouse->id,
        'code' => 'W666',
    ]);
});
