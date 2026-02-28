<?php

use App\Actions\Location\StoreLocation;
use App\Models\Location;
use App\Models\Site;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\DefaultLocationManager;

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->site = Site::factory()->create(['user_id' => $this->user->id]);
    $this->warehouse = Warehouse::factory()->for($this->site)->create();
    $this->manager = new DefaultLocationManager;
    $this->action = new StoreLocation($this->manager);
});

describe('StoreLocation Action', function () {
    test('creates a new location with provided data', function () {
        $data1 = [
            'code' => 'A01',
            'name' => 'Shelf A01',
            'is_default' => false,
        ];
        $data2 = [
            'code' => 'A02',
            'name' => 'Shelf A02',
            'is_default' => false,
        ];

        $location1 = $this->action->execute($data1, $this->warehouse);
        $location2 = $this->action->execute($data2, $this->warehouse);

        expect($location1)->toBeInstanceOf(Location::class)
            ->and($location1->code)->toBe('A01')
            ->and($location1->name)->toBe('Shelf A01')
            ->and($location1->is_default)->toBeTrue()
            ->and($location1->warehouse_id)->toBe($this->warehouse->id)
            ->and($location2)->toBeInstanceOf(Location::class)
            ->and($location2->code)->toBe('A02')
            ->and($location2->name)->toBe('Shelf A02')
            ->and($location2->is_default)->toBeFalse()
            ->and($location2->warehouse_id)->toBe($this->warehouse->id);

        $this->assertDatabaseHas('locations', [
            'code' => 'A01',
            'name' => 'Shelf A01',
            'is_default' => true,
            'warehouse_id' => $this->warehouse->id,
        ]);
        $this->assertDatabaseHas('locations', [
            'code' => 'A02',
            'name' => 'Shelf A02',
            'is_default' => false,
            'warehouse_id' => $this->warehouse->id,
        ]);
    });

    test('creates a default location and removes default from others', function () {
        // Create existing default location
        $existingDefault = Location::factory()->for($this->warehouse)->create(['is_default' => true]);

        $data = [
            'code' => 'A02',
            'name' => 'Shelf A02',
            'is_default' => true,
        ];

        $location = $this->action->execute($data, $this->warehouse);

        expect($location->is_default)->toBeTrue();
        expect($existingDefault->fresh()->is_default)->toBeFalse();
    });

    test('ensures warehouse has default location after creation', function () {
        $data = [
            'code' => 'A01',
            'name' => 'Shelf A01',
            'is_default' => false,
        ];

        $location = $this->action->execute($data, $this->warehouse);

        // Since this is the first location and not marked as default,
        // the manager should ensure it becomes default
        expect($location->fresh()->is_default)->toBeTrue();
    });

    test('handles creation when is_default is null', function () {
        $data = [
            'code' => 'A01',
            'name' => 'Shelf A01',
            // is_default is omitted (null)
        ];

        $location = $this->action->execute($data, $this->warehouse);

        expect($location->is_default)->toBeTrue() // Should default to false initially
            ->and($location->warehouse_id)->toBe($this->warehouse->id);
    });

    test('associates location with correct warehouse', function () {
        $otherSite = Site::factory()->create();
        $otherWarehouse = Warehouse::factory()->for($otherSite)->create();

        $data = [
            'code' => 'A01',
            'name' => 'Shelf A01',
            'is_default' => false,
        ];

        $location = $this->action->execute($data, $this->warehouse);

        expect($location->warehouse->id)->toBe($this->warehouse->id)
            ->and($location->warehouse->id)->not->toBe($otherWarehouse->id);
    });

    test('executes within database transaction', function () {
        // Mock the DefaultLocationManager to throw an exception
        $mockManager = $this->mock(DefaultLocationManager::class);
        $mockManager->shouldReceive('switchDefaultLocation')->andThrow(new Exception('Test exception'));
        $mockManager->shouldReceive('ensureWarehouseHasDefaultLocation')->andThrow(new Exception('Test exception'));

        $action = new StoreLocation($mockManager);

        $data = [
            'code' => 'A01',
            'name' => 'Shelf A01',
            'is_default' => true,
        ];

        expect(fn () => $action->execute($data, $this->warehouse))
            ->toThrow(Exception::class);

        // Verify no location was created due to rollback
        $this->assertDatabaseMissing('locations', [
            'code' => 'A01',
            'warehouse_id' => $this->warehouse->id,
        ]);
    });
});
