<?php

use App\Actions\Location\DeleteLocation;
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
    $this->service = new DefaultLocationManager;
    $this->action = new DeleteLocation($this->service);
});

describe('DeleteLocation Action', function () {
    test('can delete a regular location successfully', function () {
        $defaultLocation = Location::factory()->for($this->warehouse)->create(['is_default' => true]);
        $regularLocation = Location::factory()->for($this->warehouse)->create(['is_default' => false]);

        $this->action->execute($regularLocation);

        expect(Location::find($regularLocation->id))->toBeNull();
        expect(Location::find($defaultLocation->id))->not->toBeNull();
    });

    test('can delete a default location when other locations exist', function () {
        $defaultLocation = Location::factory()->for($this->warehouse)->create(['is_default' => true]);
        $otherLocation = Location::factory()->for($this->warehouse)->create(['is_default' => false]);

        $this->action->execute($defaultLocation);

        expect(Location::find($defaultLocation->id))->toBeNull();
        expect($otherLocation->fresh()->is_default)->toBeTrue(); // Should become new default
    });

    test('throws exception when trying to delete location with inventory', function () {
        // Mock the canBeDeleted method to return false
        $location = $this->mock(Location::class);
        $location->shouldReceive('canBeDeleted')->andReturn(false);
        $location->shouldReceive('getAttribute')->with('is_default')->andReturn(false);

        expect(fn () => $this->action->execute($location))
            ->toThrow(Exception::class, 'Không thể xóa vị trí này vì còn tồn kho.');
    });

    test('throws exception when trying to delete only default location', function () {
        $onlyLocation = Location::factory()->for($this->warehouse)->create(['is_default' => true]);

        expect(fn () => $this->action->execute($onlyLocation))
            ->toThrow(Exception::class, 'Không thể xóa vị trí mặc định duy nhất của kho.');
    });

    test('executes deletion within database transaction', function () {
        // Create another location first so we can delete the default one
        $otherLocation = Location::factory()->for($this->warehouse)->create(['is_default' => false]);
        $defaultLocation = Location::factory()->for($this->warehouse)->create(['is_default' => true]);

        // Mock the DefaultLocationManager to throw an exception during handleDefaultLocationDeletion
        $mockService = $this->mock(DefaultLocationManager::class);
        $mockService->shouldReceive('validateDefaultLocationDeletion')->andReturn(true);
        $mockService->shouldReceive('handleDefaultLocationDeletion')->andThrow(new Exception('Test exception'));

        $action = new DeleteLocation($mockService);

        expect(/**
         * @throws Exception
         */ fn () => $action->execute($defaultLocation))
            ->toThrow(Exception::class, 'Test exception')
            ->and(Location::find($defaultLocation->id))->not->toBeNull();

        // Location should still exist due to transaction rollback
    });

    test('handles default location reassignment after deletion', function () {
        $defaultLocation = Location::factory()->for($this->warehouse)->create(['is_default' => true]);
        $location2 = Location::factory()->for($this->warehouse)->create(['is_default' => false]);
        $location3 = Location::factory()->for($this->warehouse)->create(['is_default' => false]);

        $this->action->execute($defaultLocation);

        // One of the remaining locations should become default
        $defaultCount = Location::where('warehouse_id', $this->warehouse->id)
            ->where('is_default', true)
            ->count();

        expect($defaultCount)->toBe(1);
        expect(Location::find($defaultLocation->id))->toBeNull();
    });

    test('validates business rules before deletion', function () {
        // Test that both canBeDeleted and validateDefaultLocationDeletion are checked
        $location = Location::factory()->for($this->warehouse)->create(['is_default' => true]);

        // Mock location to return false for canBeDeleted
        $mockLocation = $this->mock(Location::class);
        $mockLocation->shouldReceive('canBeDeleted')->andReturn(false);
        $mockLocation->shouldReceive('getAttribute')->with('is_default')->andReturn(false);

        expect(fn () => $this->action->execute($mockLocation))
            ->toThrow(Exception::class, 'Không thể xóa vị trí này vì còn tồn kho.');
    });

    test('properly calls service methods in correct order', function () {
        $location = Location::factory()->for($this->warehouse)->create(['is_default' => true]);
        $otherLocation = Location::factory()->for($this->warehouse)->create(['is_default' => false]);

        // Create a spy to verify method calls
        $serviceSpy = $this->spy(DefaultLocationManager::class);
        // Make sure validation passes so we can test the method calls
        $serviceSpy->shouldReceive('validateDefaultLocationDeletion')->andReturn(true);

        $action = new DeleteLocation($serviceSpy);

        $action->execute($location);

        $serviceSpy->shouldHaveReceived('validateDefaultLocationDeletion')->with($location)->once();
        $serviceSpy->shouldHaveReceived('handleDefaultLocationDeletion')->with($location)->once();
    });
});
