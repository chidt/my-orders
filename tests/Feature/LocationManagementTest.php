<?php

use App\Models\Location;
use App\Models\Site;
use App\Models\User;
use App\Models\Warehouse;
use Spatie\Permission\Models\Permission;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    // Create location permissions
    Permission::create(['name' => 'view_warehouse_locations']);
    Permission::create(['name' => 'create_warehouse_locations']);
    Permission::create(['name' => 'edit_warehouse_locations']);
    Permission::create(['name' => 'delete_warehouse_locations']);

    $this->user = User::factory()->create();
    $this->site = Site::factory()->create(['user_id' => $this->user->id]);
    $this->warehouse = Warehouse::factory()->for($this->site)->create();

    // Give user all location permissions
    $this->user->givePermissionTo([
        'view_warehouse_locations',
        'create_warehouse_locations',
        'edit_warehouse_locations',
        'delete_warehouse_locations',
    ]);
});

describe('Location Management', function () {
    test('authenticated user can view location list for their warehouse', function () {
        $locations = Location::factory()->count(3)->for($this->warehouse)->create();

        $response = $this->actingAs($this->user)
            ->get(route('site.warehouses.locations.index', [$this->site, $this->warehouse]));

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('site/warehouses/locations/Index')
                ->has('locations.data', 3)
                ->has('site')
                ->has('warehouse')
                ->where('site.slug', $this->site->slug)
                ->where('warehouse.id', $this->warehouse->id)
            );
    });

    test('user cannot view location list without permission', function () {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $warehouse = Warehouse::factory()->for($site)->create();

        $response = $this->actingAs($user)
            ->get(route('site.warehouses.locations.index', [$site, $warehouse]));

        $response->assertStatus(403);
    });

    test('user cannot view locations from warehouse they do not own', function () {
        $otherSite = Site::factory()->create();
        $otherWarehouse = Warehouse::factory()->for($otherSite)->create();

        $response = $this->actingAs($this->user)
            ->get(route('site.warehouses.locations.index', [$otherSite, $otherWarehouse]));

        $response->assertStatus(403);
    });

    test('can view create location form', function () {
        $response = $this->actingAs($this->user)
            ->get(route('site.warehouses.locations.create', [$this->site, $this->warehouse]));

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('site/warehouses/locations/Create')
                ->has('site')
                ->has('warehouse')
                ->has('suggestedCode')
                ->where('site.slug', $this->site->slug)
                ->where('warehouse.id', $this->warehouse->id)
            );
    });

    test('can create a new location', function () {
        $response = $this->actingAs($this->user)
            ->post(route('site.warehouses.locations.store', [$this->site, $this->warehouse]), [
                'code' => 'A01',
                'name' => 'Kệ A01',
                'is_default' => false,
            ]);

        $response->assertRedirect(route('site.warehouses.locations.index', [$this->site, $this->warehouse]))
            ->assertSessionHas('success', 'Vị trí đã được tạo thành công.');

        $this->assertDatabaseHas('locations', [
            'code' => 'A01',
            'name' => 'Kệ A01',
            'warehouse_id' => $this->warehouse->id,
            'is_default' => true, // Should become default since it's the first location
        ]);
    });

    test('can create a default location', function () {
        // Create existing non-default location
        Location::factory()->for($this->warehouse)->create(['is_default' => false]);

        $response = $this->actingAs($this->user)
            ->post(route('site.warehouses.locations.store', [$this->site, $this->warehouse]), [
                'code' => 'A02',
                'name' => 'Kệ A02',
                'is_default' => true,
            ]);

        $response->assertRedirect(route('site.warehouses.locations.index', [$this->site, $this->warehouse]));

        $this->assertDatabaseHas('locations', [
            'code' => 'A02',
            'name' => 'Kệ A02',
            'warehouse_id' => $this->warehouse->id,
            'is_default' => true,
        ]);

        // Should be only one default location
        expect(Location::where('warehouse_id', $this->warehouse->id)
            ->where('is_default', true)
            ->count()
        )->toBe(1);
    });

    test('cannot create location with duplicate code in same warehouse', function () {
        Location::factory()->for($this->warehouse)->create(['code' => 'A01']);

        $response = $this->actingAs($this->user)
            ->post(route('site.warehouses.locations.store', [$this->site, $this->warehouse]), [
                'code' => 'A01',
                'name' => 'Duplicate Code',
                'is_default' => false,
            ]);

        $response->assertStatus(302)
            ->assertSessionHasErrors('code');
    });

    test('can create location with same code in different warehouse', function () {
        $otherWarehouse = Warehouse::factory()->for($this->site)->create();
        Location::factory()->for($otherWarehouse)->create(['code' => 'A01']);

        $response = $this->actingAs($this->user)
            ->post(route('site.warehouses.locations.store', [$this->site, $this->warehouse]), [
                'code' => 'A01',
                'name' => 'Same Code Different Warehouse',
                'is_default' => false,
            ]);

        $response->assertRedirect(route('site.warehouses.locations.index', [$this->site, $this->warehouse]));

        $this->assertDatabaseHas('locations', [
            'code' => 'A01',
            'name' => 'Same Code Different Warehouse',
            'warehouse_id' => $this->warehouse->id,
        ]);
    });

    test('can view single location', function () {
        $location = Location::factory()->for($this->warehouse)->create();

        $response = $this->actingAs($this->user)
            ->get(route('site.warehouses.locations.show', [$this->site, $this->warehouse, $location]));

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('site/warehouses/locations/Show')
                ->has('site')
                ->has('warehouse')
                ->has('location')
                ->where('location.id', $location->id)
            );
    });

    test('can view edit location form', function () {
        $location = Location::factory()->for($this->warehouse)->create();

        $response = $this->actingAs($this->user)
            ->get(route('site.warehouses.locations.edit', [$this->site, $this->warehouse, $location]));

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('site/warehouses/locations/Edit')
                ->has('site')
                ->has('warehouse')
                ->has('location')
                ->where('location.id', $location->id)
            );
    });

    test('can update location', function () {
        $location = Location::factory()->for($this->warehouse)->create([
            'code' => 'A01',
            'name' => 'Old Name',
            'is_default' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->patch(route('site.warehouses.locations.update', [$this->site, $this->warehouse, $location]), [
                'code' => 'A02',
                'name' => 'New Name',
                'is_default' => false,
            ]);

        $response->assertRedirect(route('site.warehouses.locations.index', [$this->site, $this->warehouse]))
            ->assertSessionHas('success', 'Vị trí đã được cập nhật thành công.');

        $this->assertDatabaseHas('locations', [
            'id' => $location->id,
            'code' => 'A02',
            'name' => 'New Name',
            'is_default' => false,
        ]);
    });

    test('can update location to become default', function () {
        $defaultLocation = Location::factory()->for($this->warehouse)->create(['is_default' => true]);
        $regularLocation = Location::factory()->for($this->warehouse)->create(['is_default' => false]);

        $response = $this->actingAs($this->user)
            ->patch(route('site.warehouses.locations.update', [$this->site, $this->warehouse, $regularLocation]), [
                'code' => $regularLocation->code,
                'name' => $regularLocation->name,
                'is_default' => true,
            ]);

        $response->assertRedirect(route('site.warehouses.locations.index', [$this->site, $this->warehouse]));

        expect($regularLocation->fresh()->is_default)->toBeTrue();
        expect($defaultLocation->fresh()->is_default)->toBeFalse();
    });

    test('can delete a regular location', function () {
        $defaultLocation = Location::factory()->for($this->warehouse)->create(['is_default' => true]);
        $regularLocation = Location::factory()->for($this->warehouse)->create(['is_default' => false]);

        $response = $this->actingAs($this->user)
            ->delete(route('site.warehouses.locations.destroy', [$this->site, $this->warehouse, $regularLocation]));

        $response->assertRedirect(route('site.warehouses.locations.index', [$this->site, $this->warehouse]))
            ->assertSessionHas('success', 'Vị trí đã được xóa thành công.');

        $this->assertModelMissing($regularLocation);
        expect($defaultLocation->fresh())->not->toBeNull();
    });

    test('cannot delete only default location', function () {
        $onlyLocation = Location::factory()->for($this->warehouse)->create(['is_default' => true]);

        $response = $this->actingAs($this->user)
            ->delete(route('site.warehouses.locations.destroy', [$this->site, $this->warehouse, $onlyLocation]));

        $response->assertRedirect(route('site.warehouses.locations.index', [$this->site, $this->warehouse]))
            ->assertSessionHas('error', 'Không thể xóa vị trí mặc định duy nhất của kho.');

        expect($onlyLocation->fresh())->not->toBeNull();
    });

    test('can delete default location when other locations exist', function () {
        $defaultLocation = Location::factory()->for($this->warehouse)->create(['is_default' => true]);
        $otherLocation = Location::factory()->for($this->warehouse)->create(['is_default' => false]);

        $response = $this->actingAs($this->user)
            ->delete(route('site.warehouses.locations.destroy', [$this->site, $this->warehouse, $defaultLocation]));

        $response->assertRedirect(route('site.warehouses.locations.index', [$this->site, $this->warehouse]))
            ->assertSessionHas('success', 'Vị trí đã được xóa thành công.');

        $this->assertModelMissing($defaultLocation);
        expect($otherLocation->fresh()->is_default)->toBeTrue(); // Should become new default
    });

    test('validates required fields when creating location', function () {
        $response = $this->actingAs($this->user)
            ->post(route('site.warehouses.locations.store', [$this->site, $this->warehouse]), [
                // Missing required fields
            ]);

        $response->assertStatus(302)
            ->assertSessionHasErrors(['code', 'name']);
    });

    test('validates code format when creating location', function () {
        $response = $this->actingAs($this->user)
            ->post(route('site.warehouses.locations.store', [$this->site, $this->warehouse]), [
                'code' => 'invalid-code!',
                'name' => 'Valid Name',
                'is_default' => false,
            ]);

        $response->assertStatus(302)
            ->assertSessionHasErrors('code');
    });

    test('orders locations by default status and code', function () {
        $location1 = Location::factory()->for($this->warehouse)->create(['code' => 'B01', 'is_default' => false]);
        $location2 = Location::factory()->for($this->warehouse)->create(['code' => 'A01', 'is_default' => true]);
        $location3 = Location::factory()->for($this->warehouse)->create(['code' => 'A02', 'is_default' => false]);

        $response = $this->actingAs($this->user)
            ->get(route('site.warehouses.locations.index', [$this->site, $this->warehouse]));

        $response->assertStatus(200);

        // Default locations should come first, then ordered by code
        $locationIds = $response->inertiaPage()['props']['locations']['data'];
        expect($locationIds[0]['id'])->toBe($location2->id); // Default first
        expect($locationIds[1]['id'])->toBe($location3->id); // A02 before B01
        expect($locationIds[2]['id'])->toBe($location1->id); // B01 last
    });
});
