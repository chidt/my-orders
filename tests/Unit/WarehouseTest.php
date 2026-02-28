<?php

use App\Models\Location;
use App\Models\Site;
use App\Models\Warehouse;

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->site = Site::factory()->create();
});

test('warehouse belongs to site', function () {
    $warehouse = Warehouse::factory()->forSite($this->site)->create();

    expect($warehouse->site)->toBeInstanceOf(Site::class)
        ->and($warehouse->site->id)->toBe($this->site->id);
});

test('warehouse has many locations', function () {
    $warehouse = Warehouse::factory()->create();
    Location::factory()->count(3)->create(['warehouse_id' => $warehouse->id]);

    expect($warehouse->locations)->toHaveCount(3)
        ->and($warehouse->locations->first())->toBeInstanceOf(Location::class);
});

test('warehouse scope for site filters correctly', function () {
    $site1 = Site::factory()->create();
    $site2 = Site::factory()->create();

    $warehouse1 = Warehouse::factory()->forSite($site1)->create();
    $warehouse2 = Warehouse::factory()->forSite($site2)->create();

    $site1Warehouses = Warehouse::forSite($site1->id)->get();
    $site2Warehouses = Warehouse::forSite($site2->id)->get();

    expect($site1Warehouses)->toHaveCount(1)
        ->and($site2Warehouses)->toHaveCount(1)
        ->and($site1Warehouses->first()->id)->toBe($warehouse1->id)
        ->and($site2Warehouses->first()->id)->toBe($warehouse2->id);
});

test('warehouse scope with locations count works', function () {
    $warehouse = Warehouse::factory()->create();
    Location::factory()->count(5)->create(['warehouse_id' => $warehouse->id]);

    $warehouseWithCount = Warehouse::withLocationsCount()->find($warehouse->id);

    expect($warehouseWithCount->locations_count)->toBe(5);
});

test('warehouse gets default location correctly', function () {
    $warehouse = Warehouse::factory()->create();

    $defaultLocation = Location::factory()->create([
        'warehouse_id' => $warehouse->id,
        'is_default' => true,
    ]);
    Location::factory()->count(2)->create([
        'warehouse_id' => $warehouse->id,
        'is_default' => false,
    ]);

    expect($warehouse->defaultLocation->id)->toBe($defaultLocation->id);
});

test('warehouse can be deleted when no locations', function () {
    $warehouse = Warehouse::factory()->create();

    expect($warehouse->canBeDeleted())->toBeTrue();
});

test('warehouse cannot be deleted when has locations', function () {
    $warehouse = Warehouse::factory()->create();
    Location::factory()->create(['warehouse_id' => $warehouse->id]);

    expect($warehouse->canBeDeleted())->toBeFalse();
});

test('warehouse generates unique code', function () {
    $site = Site::factory()->create();

    // Create warehouse with existing code
    Warehouse::factory()->forSite($site)->withCode('W001')->create();

    $newCode = Warehouse::generateUniqueCode($site);

    expect($newCode)->not->toBe('W001')
        ->and($newCode)->toStartWith('W')
        ->and($newCode)->toMatch('/^W\d{3}$/');
});

test('warehouse generates unique code with custom prefix', function () {
    $site = Site::factory()->create();

    // Generate first code and create warehouse with it
    $code1 = Warehouse::generateUniqueCode($site, 'KHO');
    Warehouse::factory()->forSite($site)->withCode($code1)->create();

    // Generate second code - should be different from first
    $code2 = Warehouse::generateUniqueCode($site, 'KHO');

    expect($code1)->toStartWith('KHO')
        ->and($code2)->toStartWith('KHO')
        ->and($code1)->not->toBe($code2)
        ->and($code1)->toBe('KHO001')
        ->and($code2)->toBe('KHO002');
});

test('warehouse has correct fillable attributes', function () {
    $warehouse = new Warehouse;
    $fillable = $warehouse->getFillable();

    expect($fillable)->toBe(['code', 'name', 'address']);
});

test('warehouse has correct casts', function () {
    $warehouse = new Warehouse;
    $casts = $warehouse->getCasts();

    expect($casts['created_at'])->toBe('datetime')
        ->and($casts['updated_at'])->toBe('datetime');
});
