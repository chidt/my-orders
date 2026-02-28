<?php

use App\Models\Location;
use App\Models\Site;
use App\Models\User;
use App\Models\Warehouse;

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->site = Site::factory()->create(['user_id' => $this->user->id]);
    $this->warehouse = Warehouse::factory()->for($this->site)->create();
});

describe('Location Model', function () {
    test('can create a location with required attributes', function () {
        $location = Location::factory()->for($this->warehouse)->create([
            'code' => 'A01',
            'name' => 'Kệ A01',
            'is_default' => false,
        ]);

        expect($location->code)->toBe('A01')
            ->and($location->name)->toBe('Kệ A01')
            ->and($location->is_default)->toBeFalse()
            ->and($location->warehouse_id)->toBe($this->warehouse->id);
    });

    test('belongs to a warehouse', function () {
        $location = Location::factory()->for($this->warehouse)->create();

        expect($location->warehouse)->toBeInstanceOf(Warehouse::class)
            ->and($location->warehouse->id)->toBe($this->warehouse->id);
    });

    test('casts is_default to boolean', function () {
        $location = Location::factory()->for($this->warehouse)->create(['is_default' => 1]);

        expect($location->is_default)->toBeTrue();
    });

    test('scopeForWarehouse filters locations by warehouse', function () {
        $otherWarehouse = Warehouse::factory()->for($this->site)->create();

        $location1 = Location::factory()->for($this->warehouse)->create();
        $location2 = Location::factory()->for($otherWarehouse)->create();

        $warehouseLocations = Location::forWarehouse($this->warehouse->id)->get();

        expect($warehouseLocations)->toHaveCount(1)
            ->and($warehouseLocations->first()->id)->toBe($location1->id);
    });

    test('scopeDefaults filters default locations only', function () {
        $location1 = Location::factory()->for($this->warehouse)->create(['is_default' => true]);
        $location2 = Location::factory()->for($this->warehouse)->create(['is_default' => false]);

        $defaultLocations = Location::defaults()->get();

        expect($defaultLocations)->toHaveCount(1)
            ->and($defaultLocations->first()->id)->toBe($location1->id);
    });

    test('canBeDeleted returns true by default', function () {
        $location = Location::factory()->for($this->warehouse)->create();

        expect($location->canBeDeleted())->toBeTrue();
    });

    test('isOnlyDefaultInWarehouse detects single default location', function () {
        $location1 = Location::factory()->for($this->warehouse)->create(['is_default' => true]);
        $location2 = Location::factory()->for($this->warehouse)->create(['is_default' => false]);

        expect($location1->isOnlyDefaultInWarehouse())->toBeTrue()
            ->and($location2->isOnlyDefaultInWarehouse())->toBeFalse();
    });

    test('isOnlyDefaultInWarehouse returns false when multiple defaults exist', function () {
        $location1 = Location::factory()->for($this->warehouse)->create(['is_default' => true]);
        $location2 = Location::factory()->for($this->warehouse)->create(['is_default' => true]);

        expect($location1->isOnlyDefaultInWarehouse())->toBeFalse()
            ->and($location2->isOnlyDefaultInWarehouse())->toBeFalse();
    });

    test('getQtyInStockAttribute returns zero for now', function () {
        $location = Location::factory()->for($this->warehouse)->create();

        expect($location->qty_in_stock)->toBe(0);
    });

    test('generateUniqueCode creates unique codes within warehouse', function () {
        Location::factory()->for($this->warehouse)->create(['code' => 'A01']);
        Location::factory()->for($this->warehouse)->create(['code' => 'A02']);

        $code = Location::generateUniqueCode($this->warehouse);

        expect($code)->toMatch('/^A\d{2}$/')
            ->and(Location::where('warehouse_id', $this->warehouse->id)->where('code', $code)->exists())
            ->toBeFalse();
    });

    test('generateUniqueCode with custom prefix', function () {
        $code = Location::generateUniqueCode($this->warehouse, 'B');

        expect($code)->toMatch('/^B\d{2}$/')
            ->and($code)->toBe('B01');
    });

    test('enforces unique code within warehouse constraint', function () {
        $location1 = Location::factory()->for($this->warehouse)->create(['code' => 'A01']);

        $otherWarehouse = Warehouse::factory()->for($this->site)->create();
        // Same code in different warehouse should be allowed
        $location2 = Location::factory()->for($otherWarehouse)->create(['code' => 'A01']);

        expect($location1->code)->toBe('A01')
            ->and($location2->code)->toBe('A01')
            ->and($location1->warehouse_id)->not->toBe($location2->warehouse_id);
    });
});
