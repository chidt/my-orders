<?php

use App\Models\Location;
use App\Models\Site;
use App\Models\User;
use App\Models\Warehouse;
use Spatie\Permission\Models\Permission;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    // Create permissions
    Permission::create(['name' => 'manage_warehouses']);
    Permission::create(['name' => 'create_warehouses']);
    Permission::create(['name' => 'view_warehouses']);
    Permission::create(['name' => 'edit_warehouses']);
    Permission::create(['name' => 'delete_warehouses']);

    $this->user = User::factory()->create();
    $this->site = Site::factory()->create(['user_id' => $this->user->id]);

    // Give user all warehouse permissions
    $this->user->givePermissionTo([
        'manage_warehouses',
        'create_warehouses',
        'view_warehouses',
        'edit_warehouses',
        'delete_warehouses',
    ]);
});

test('authenticated user can view warehouse list for their site', function () {
    $warehouses = Warehouse::factory()->count(3)->forSite($this->site)->create();

    $response = $this->actingAs($this->user)
        ->get(route('site.warehouses.index', $this->site->slug));

    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('site/warehouses/Index')
            ->has('warehouses.data', 3)
            ->has('site')
            ->where('site.slug', $this->site->slug)
        );
});

test('user cannot view warehouse list without permission', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->get(route('site.warehouses.index', $site->slug));

    $response->assertStatus(403);
});

test('user cannot view warehouses for site they do not own', function () {
    $otherUser = User::factory()->create();
    $otherSite = Site::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($this->user)
        ->get(route('site.warehouses.index', $otherSite->slug));

    $response->assertStatus(403);
});

test('authenticated user can view create warehouse form', function () {
    $response = $this->actingAs($this->user)
        ->get(route('site.warehouses.create', $this->site->slug));

    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('site/warehouses/Create')
            ->has('site')
            ->has('suggestedCode')
        );
});

test('authenticated user can create a warehouse', function () {
    $warehouseData = [
        'code' => 'W001',
        'name' => 'Test Warehouse',
        'address' => '123 Test Street, Test City',
    ];

    $response = $this->actingAs($this->user)
        ->post(route('site.warehouses.store', $this->site->slug), $warehouseData);

    $response->assertRedirect(route('site.warehouses.index', $this->site->slug))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('warehouses', [
        'code' => 'W001',
        'name' => 'Test Warehouse',
        'address' => '123 Test Street, Test City',
        'site_id' => $this->site->id,
    ]);
});

test('warehouse creation validates required fields', function () {
    $response = $this->actingAs($this->user)
        ->post(route('site.warehouses.store', $this->site->slug), []);

    $response->assertSessionHasErrors(['code', 'name', 'address']);
});

test('warehouse creation validates unique code within site', function () {
    Warehouse::factory()->forSite($this->site)->withCode('W001')->create();

    $response = $this->actingAs($this->user)
        ->post(route('site.warehouses.store', $this->site->slug), [
            'code' => 'W001',
            'name' => 'Test Warehouse',
            'address' => '123 Test Street',
        ]);

    $response->assertSessionHasErrors(['code']);
});

test('warehouse code can be same across different sites', function () {
    $otherSite = Site::factory()->create();
    Warehouse::factory()->forSite($otherSite)->withCode('W001')->create();

    $warehouseData = [
        'code' => 'W001',
        'name' => 'Test Warehouse',
        'address' => '123 Test Street',
    ];

    $response = $this->actingAs($this->user)
        ->post(route('site.warehouses.store', $this->site->slug), $warehouseData);

    $response->assertRedirect();
    $this->assertDatabaseHas('warehouses', [
        'code' => 'W001',
        'site_id' => $this->site->id,
    ]);
});

test('authenticated user can view edit warehouse form', function () {
    $warehouse = Warehouse::factory()->forSite($this->site)->create();

    $response = $this->actingAs($this->user)
        ->get(route('site.warehouses.edit', [$this->site->slug, $warehouse->id]));

    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('site/warehouses/Edit')
            ->has('site')
            ->has('warehouse')
            ->where('warehouse.id', $warehouse->id)
        );
});

test('authenticated user can update a warehouse', function () {
    $warehouse = Warehouse::factory()->forSite($this->site)->create();

    $updateData = [
        'code' => 'W999',
        'name' => 'Updated Warehouse',
        'address' => '456 Updated Street',
    ];

    $response = $this->actingAs($this->user)
        ->put(route('site.warehouses.update', [$this->site->slug, $warehouse->id]), $updateData);

    $response->assertRedirect(route('site.warehouses.index', $this->site->slug))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('warehouses', [
        'id' => $warehouse->id,
        'code' => 'W999',
        'name' => 'Updated Warehouse',
        'address' => '456 Updated Street',
    ]);
});

test('warehouse update validates unique code within site excluding current', function () {
    $warehouse1 = Warehouse::factory()->forSite($this->site)->withCode('W001')->create();
    $warehouse2 = Warehouse::factory()->forSite($this->site)->withCode('W002')->create();

    $response = $this->actingAs($this->user)
        ->put(route('site.warehouses.update', [$this->site->slug, $warehouse2->id]), [
            'code' => 'W001',
            'name' => 'Updated Warehouse',
            'address' => '456 Updated Street',
        ]);

    $response->assertSessionHasErrors(['code']);
});

test('authenticated user can delete warehouse without locations', function () {
    $warehouse = Warehouse::factory()->forSite($this->site)->create();

    $response = $this->actingAs($this->user)
        ->delete(route('site.warehouses.destroy', [$this->site->slug, $warehouse->id]));

    $response->assertRedirect(route('site.warehouses.index', $this->site->slug))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('warehouses', ['id' => $warehouse->id]);
});

test('authenticated user cannot delete warehouse with locations', function () {
    $warehouse = Warehouse::factory()->forSite($this->site)->create();
    Location::factory()->create(['warehouse_id' => $warehouse->id]);

    $response = $this->actingAs($this->user)
        ->delete(route('site.warehouses.destroy', [$this->site->slug, $warehouse->id]));

    $response->assertRedirect(route('site.warehouses.index', $this->site->slug))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('warehouses', ['id' => $warehouse->id]);
});

test('user cannot access warehouse of different site', function () {
    $otherUser = User::factory()->create();
    $otherSite = Site::factory()->create(['user_id' => $otherUser->id]);
    $warehouse = Warehouse::factory()->forSite($otherSite)->create();

    // Try to edit
    $response = $this->actingAs($this->user)
        ->get(route('site.warehouses.edit', [$otherSite->slug, $warehouse->id]));
    $response->assertStatus(403);

    // Try to update
    $response = $this->actingAs($this->user)
        ->put(route('site.warehouses.update', [$otherSite->slug, $warehouse->id]), [
            'code' => 'HACK',
            'name' => 'Hacked',
            'address' => 'Hacked',
        ]);
    $response->assertStatus(403);

    // Try to delete
    $response = $this->actingAs($this->user)
        ->delete(route('site.warehouses.destroy', [$otherSite->slug, $warehouse->id]));
    $response->assertStatus(403);
});

test('warehouse list includes locations count', function () {
    $warehouse = Warehouse::factory()->forSite($this->site)->create();
    Location::factory()->count(3)->create(['warehouse_id' => $warehouse->id]);

    $response = $this->actingAs($this->user)
        ->get(route('site.warehouses.index', $this->site->slug));

    $response->assertInertia(fn ($page) => $page->has('warehouses.data.0.locations_count')
        ->where('warehouses.data.0.locations_count', 3)
    );
});
