<?php

use App\Models\Location;
use App\Models\Site;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\DefaultLocationManager;

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->service = new DefaultLocationManager;
    $this->user = User::factory()->create();
    $this->site = Site::factory()->create(['user_id' => $this->user->id]);
    $this->warehouse = Warehouse::factory()->for($this->site)->create();
});

describe('DefaultLocationManager', function () {
    test('ensureWarehouseHasDefaultLocation creates default when none exists', function () {
        $location1 = Location::factory()->for($this->warehouse)->create(['is_default' => false]);
        $location2 = Location::factory()->for($this->warehouse)->create(['is_default' => false]);

        $this->service->ensureWarehouseHasDefaultLocation($this->warehouse);

        $this->warehouse->refresh();
        $defaultLocations = $this->warehouse->locations()->where('is_default', true)->get();

        expect($defaultLocations)->toHaveCount(1)
            ->and($defaultLocations->first()->id)->toBe($location1->id); // Should be the oldest
    });

    test('ensureWarehouseHasDefaultLocation does nothing when default exists', function () {
        $defaultLocation = Location::factory()->for($this->warehouse)->create(['is_default' => true]);
        $regularLocation = Location::factory()->for($this->warehouse)->create(['is_default' => false]);

        $this->service->ensureWarehouseHasDefaultLocation($this->warehouse);

        $this->warehouse->refresh();
        $defaultLocations = $this->warehouse->locations()->where('is_default', true)->get();

        expect($defaultLocations)->toHaveCount(1)
            ->and($defaultLocations->first()->id)->toBe($defaultLocation->id);
    });

    test('switchDefaultLocation removes default from other locations', function () {
        $location1 = Location::factory()->for($this->warehouse)->create(['is_default' => true]);
        $location2 = Location::factory()->for($this->warehouse)->create(['is_default' => false]);

        // Make location2 the new default
        $location2->update(['is_default' => true]);
        $this->service->switchDefaultLocation($location2);

        $location1->refresh();
        $location2->refresh();

        expect($location1->is_default)->toBeFalse()
            ->and($location2->is_default)->toBeTrue();
    });

    test('switchDefaultLocation does nothing for non-default locations', function () {
        $location1 = Location::factory()->for($this->warehouse)->create(['is_default' => true]);
        $location2 = Location::factory()->for($this->warehouse)->create(['is_default' => false]);

        $this->service->switchDefaultLocation($location2);

        $location1->refresh();
        $location2->refresh();

        expect($location1->is_default)->toBeTrue()
            ->and($location2->is_default)->toBeFalse();
    });

    test('validateDefaultLocationDeletion allows non-default location deletion', function () {
        $location = Location::factory()->for($this->warehouse)->create(['is_default' => false]);

        $canDelete = $this->service->validateDefaultLocationDeletion($location);

        expect($canDelete)->toBeTrue();
    });

    test('validateDefaultLocationDeletion allows default deletion when multiple locations exist', function () {
        $defaultLocation = Location::factory()->for($this->warehouse)->create(['is_default' => true]);
        $regularLocation = Location::factory()->for($this->warehouse)->create(['is_default' => false]);

        $canDelete = $this->service->validateDefaultLocationDeletion($defaultLocation);

        expect($canDelete)->toBeTrue();
    });

    test('validateDefaultLocationDeletion prevents deletion of only location', function () {
        $onlyLocation = Location::factory()->for($this->warehouse)->create(['is_default' => true]);

        $canDelete = $this->service->validateDefaultLocationDeletion($onlyLocation);

        expect($canDelete)->toBeFalse();
    });

    test('getDefaultLocationForWarehouse returns correct default', function () {
        $defaultLocation = Location::factory()->for($this->warehouse)->create(['is_default' => true]);
        $regularLocation = Location::factory()->for($this->warehouse)->create(['is_default' => false]);

        $result = $this->service->getDefaultLocationForWarehouse($this->warehouse);

        expect($result->id)->toBe($defaultLocation->id);
    });

    test('getDefaultLocationForWarehouse returns null when no default exists', function () {
        Location::factory()->for($this->warehouse)->create(['is_default' => false]);

        $result = $this->service->getDefaultLocationForWarehouse($this->warehouse);

        expect($result)->toBeNull();
    });

    test('createDefaultLocationForWarehouse creates new default location', function () {
        $result = $this->service->createDefaultLocationForWarehouse($this->warehouse);

        expect($result)->toBeInstanceOf(Location::class)
            ->and($result->warehouse_id)->toBe($this->warehouse->id)
            ->and($result->is_default)->toBeTrue()
            ->and($result->name)->toBe('Vị trí mặc định')
            ->and($result->code)->toMatch('/^A\d{2}$/');
    });

    test('handleDefaultLocationDeletion reassigns default to another location', function () {
        $defaultLocation = Location::factory()->for($this->warehouse)->create(['is_default' => true]);
        $otherLocation = Location::factory()->for($this->warehouse)->create(['is_default' => false]);

        $this->service->handleDefaultLocationDeletion($defaultLocation);

        $otherLocation->refresh();
        expect($otherLocation->is_default)->toBeTrue();
    });

    test('handleDefaultLocationDeletion does nothing for non-default deletion', function () {
        $defaultLocation = Location::factory()->for($this->warehouse)->create(['is_default' => true]);
        $regularLocation = Location::factory()->for($this->warehouse)->create(['is_default' => false]);

        $this->service->handleDefaultLocationDeletion($regularLocation);

        $defaultLocation->refresh();
        expect($defaultLocation->is_default)->toBeTrue();
    });

    test('handleDefaultLocationDeletion handles last location gracefully', function () {
        $onlyLocation = Location::factory()->for($this->warehouse)->create(['is_default' => true]);

        // This shouldn't throw an error even though no other location exists
        $this->service->handleDefaultLocationDeletion($onlyLocation);

        // No new default should be created automatically
        $defaultCount = Location::where('warehouse_id', $this->warehouse->id)
            ->where('is_default', true)
            ->count();

        expect($defaultCount)->toBe(1);
    });
});
