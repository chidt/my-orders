<?php

use App\Actions\Warehouse\DeleteWarehouse;
use App\Models\Location;
use App\Models\Site;
use App\Models\Warehouse;

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->action = new DeleteWarehouse;
    $this->site = Site::factory()->create();
    $this->warehouse = Warehouse::factory()->forSite($this->site)->create();
});

test('delete warehouse removes warehouse without locations', function () {
    $warehouseId = $this->warehouse->id;

    $this->action->execute($this->warehouse);

    $this->assertDatabaseMissing('warehouses', ['id' => $warehouseId]);
});

test('delete warehouse throws exception when warehouse has locations', function () {
    Location::factory()->create(['warehouse_id' => $this->warehouse->id]);

    expect(fn () => $this->action->execute($this->warehouse))
        ->toThrow(Exception::class, 'Không thể xóa kho vì vẫn còn vị trí. Vui lòng xóa tất cả vị trí trước.');

    $this->assertDatabaseHas('warehouses', ['id' => $this->warehouse->id]);
});

test('delete warehouse uses database transaction', function () {
    $warehouseId = $this->warehouse->id;

    // This should complete successfully in a transaction
    $this->action->execute($this->warehouse);

    $this->assertDatabaseMissing('warehouses', ['id' => $warehouseId]);
});

test('delete warehouse respects business rules', function () {
    // Create multiple locations
    Location::factory()->count(3)->create(['warehouse_id' => $this->warehouse->id]);

    expect(fn () => $this->action->execute($this->warehouse))
        ->toThrow(Exception::class);

    // Warehouse should still exist
    $this->assertDatabaseHas('warehouses', ['id' => $this->warehouse->id]);
});

test('delete warehouse allows deletion after locations removed', function () {
    $location = Location::factory()->create(['warehouse_id' => $this->warehouse->id]);

    // First attempt should fail
    expect(fn () => $this->action->execute($this->warehouse))
        ->toThrow(Exception::class);

    // Remove location
    $location->delete();

    // Now deletion should succeed
    $warehouseId = $this->warehouse->id;
    $this->action->execute($this->warehouse->fresh());

    $this->assertDatabaseMissing('warehouses', ['id' => $warehouseId]);
});
