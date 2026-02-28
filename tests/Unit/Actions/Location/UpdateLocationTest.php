<?php

use App\Actions\Location\UpdateLocation;
use App\Models\Location;
use App\Models\Site;
use App\Models\Warehouse;
use App\Services\DefaultLocationManager;

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->site = Site::factory()->create();
    $this->warehouse = Warehouse::factory()->for($this->site)->create();
    $this->manager = new DefaultLocationManager;
    $this->action = new UpdateLocation($this->manager);
});

it('updates location with provided data', function () {
    $location = Location::factory()->for($this->warehouse)->create([
        'code' => 'A01',
        'name' => 'Old Name',
        'is_default' => false,
    ]);

    $data = [
        'code' => 'A02',
        'name' => 'New Name',
        'is_default' => false,
    ];

    $updatedLocation = $this->action->execute($location, $data);

    expect($updatedLocation)
        ->code->toBe('A02')
        ->name->toBe('New Name')
        ->is_default->toBeFalse();

    $this->assertDatabaseHas('locations', [
        'id' => $location->id,
        'code' => 'A02',
        'name' => 'New Name',
        'is_default' => false,
    ]);
});

it('switches to default and removes default from others', function () {
    $existingDefault = Location::factory()->for($this->warehouse)->create(['is_default' => true]);
    $location = Location::factory()->for($this->warehouse)->create(['is_default' => false]);

    $data = [
        'code' => $location->code,
        'name' => $location->name,
        'is_default' => true,
    ];

    $this->action->execute($location, $data);

    expect($location->fresh()->is_default)->toBeTrue()
        ->and($existingDefault->fresh()->is_default)->toBeFalse();
});

it('removes default status and ensures warehouse has default', function () {
    $defaultLocation = Location::factory()->for($this->warehouse)->create(['is_default' => true]);
    $otherLocation = Location::factory()->for($this->warehouse)->create(['is_default' => false]);

    $data = [
        'code' => $defaultLocation->code,
        'name' => $defaultLocation->name,
        'is_default' => false,
    ];

    $this->action->execute($defaultLocation, $data);

    expect($defaultLocation->fresh()->is_default)->toBeTrue()
        ->and($otherLocation->fresh()->is_default)->toBeFalse();
    // Should become default
});

it('handles update when is_default is null', function () {
    $location = Location::factory()->for($this->warehouse)->create(['is_default' => true]);

    $data = [
        'code' => 'A02',
        'name' => 'Updated Name',
        // is_default is omitted (null)
    ];

    $updatedLocation = $this->action->execute($location, $data);

    expect($updatedLocation)
        ->code->toBe('A02')
        ->name->toBe('Updated Name')
        ->is_default->toBeTrue();
});

it('does not change default status when staying the same', function () {
    $defaultLocation = Location::factory()->for($this->warehouse)->create(['is_default' => true]);
    $regularLocation = Location::factory()->for($this->warehouse)->create(['is_default' => false]);

    $data = [
        'code' => 'A02',
        'name' => 'Updated Name',
        'is_default' => true, // Stays the same
    ];

    $this->action->execute($defaultLocation, $data);

    expect($defaultLocation->fresh()->is_default)->toBeTrue()
        ->and($regularLocation->fresh()->is_default)->toBeFalse();
});

it('executes within database transaction', function () {
    $location = Location::factory()->for($this->warehouse)->create([
        'code' => 'A01',
        'name' => 'Original Name',
        'is_default' => false,
    ]);

    // Mock the DefaultLocationManager to throw an exception
    $mockManager = $this->mock(DefaultLocationManager::class);
    $mockManager->shouldReceive('switchDefaultLocation')->andThrow(new \Exception('Test exception'));
    $mockManager->shouldReceive('ensureWarehouseHasDefaultLocation')->andThrow(new \Exception('Test exception'));

    $action = new UpdateLocation($mockManager);

    $data = [
        'code' => 'A02',
        'name' => 'New Name',
        'is_default' => true,
    ];

    expect(function () use ($action, $location, $data) {
        $action->execute($location, $data);
    })->toThrow(\Exception::class);

    // Verify no changes were made due to rollback
    expect($location->fresh())
        ->code->toBe('A01')
        ->name->toBe('Original Name')
        ->is_default->toBeFalse();
});

it('returns fresh model instance', function () {
    $location = Location::factory()->for($this->warehouse)->create();

    $data = [
        'code' => 'A02',
        'name' => 'Updated Name',
        'is_default' => false,
    ];

    $updatedLocation = $this->action->execute($location, $data);

    expect($updatedLocation->id)->toBe($location->id);
    expect($updatedLocation->code)->toBe('A02');
    expect($updatedLocation->name)->toBe('Updated Name');
});
