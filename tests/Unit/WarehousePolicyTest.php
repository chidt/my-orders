<?php

use App\Models\Location;
use App\Models\Site;
use App\Models\User;
use App\Models\Warehouse;
use App\Policies\WarehousePolicy;
use Spatie\Permission\Models\Permission;

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    // Create permissions
    Permission::create(['name' => 'manage_warehouses']);
    Permission::create(['name' => 'create_warehouses']);
    Permission::create(['name' => 'view_warehouses']);
    Permission::create(['name' => 'edit_warehouses']);
    Permission::create(['name' => 'delete_warehouses']);

    $this->policy = new WarehousePolicy;
    $this->user = User::factory()->create();
    $this->site = Site::factory()->create(['user_id' => $this->user->id]);
    $this->otherUser = User::factory()->create();
    $this->otherSite = Site::factory()->create(['user_id' => $this->otherUser->id]);
});

test('user can view any warehouses if has permission and owns site', function () {
    $this->user->givePermissionTo('manage_warehouses');

    $result = $this->policy->viewAny($this->user, $this->site);

    expect($result)->toBeTrue();
});

test('user cannot view any warehouses without permission', function () {
    $result = $this->policy->viewAny($this->user, $this->site);

    expect($result)->toBeFalse();
});

test('user cannot view any warehouses if does not own site', function () {
    $this->user->givePermissionTo('manage_warehouses');

    $result = $this->policy->viewAny($this->user, $this->otherSite);

    expect($result)->toBeFalse();
});

test('user can view warehouse if has permission and owns it', function () {
    $this->user->givePermissionTo('view_warehouses');
    $warehouse = Warehouse::factory()->forSite($this->site)->create();

    $result = $this->policy->view($this->user, $warehouse);

    expect($result)->toBeTrue();
});

test('user cannot view warehouse without permission', function () {
    $warehouse = Warehouse::factory()->forSite($this->site)->create();

    $result = $this->policy->view($this->user, $warehouse);

    expect($result)->toBeFalse();
});

test('user cannot view warehouse they do not own', function () {
    $this->user->givePermissionTo('view_warehouses');
    $warehouse = Warehouse::factory()->forSite($this->otherSite)->create();

    $result = $this->policy->view($this->user, $warehouse);

    expect($result)->toBeFalse();
});

test('user can create warehouse if has permission and owns site', function () {
    $this->user->givePermissionTo('create_warehouses');

    $result = $this->policy->create($this->user, $this->site);

    expect($result)->toBeTrue();
});

test('user cannot create warehouse without permission', function () {
    $result = $this->policy->create($this->user, $this->site);

    expect($result)->toBeFalse();
});

test('user cannot create warehouse if does not own site', function () {
    $this->user->givePermissionTo('create_warehouses');

    $result = $this->policy->create($this->user, $this->otherSite);

    expect($result)->toBeFalse();
});

test('user can update warehouse if has permission and owns it', function () {
    $this->user->givePermissionTo('edit_warehouses');
    $warehouse = Warehouse::factory()->forSite($this->site)->create();

    $result = $this->policy->update($this->user, $warehouse);

    expect($result)->toBeTrue();
});

test('user cannot update warehouse without permission', function () {
    $warehouse = Warehouse::factory()->forSite($this->site)->create();

    $result = $this->policy->update($this->user, $warehouse);

    expect($result)->toBeFalse();
});

test('user cannot update warehouse they do not own', function () {
    $this->user->givePermissionTo('edit_warehouses');
    $warehouse = Warehouse::factory()->forSite($this->otherSite)->create();

    $result = $this->policy->update($this->user, $warehouse);

    expect($result)->toBeFalse();
});

test('user can delete warehouse if has permission and owns it and no locations', function () {
    $this->user->givePermissionTo('delete_warehouses');
    $warehouse = Warehouse::factory()->forSite($this->site)->create();

    $result = $this->policy->delete($this->user, $warehouse);

    expect($result)->toBeTrue();
});

test('user cannot delete warehouse without permission', function () {
    $warehouse = Warehouse::factory()->forSite($this->site)->create();

    $result = $this->policy->delete($this->user, $warehouse);

    expect($result)->toBeFalse();
});

test('user cannot delete warehouse they do not own', function () {
    $this->user->givePermissionTo('delete_warehouses');
    $warehouse = Warehouse::factory()->forSite($this->otherSite)->create();

    $result = $this->policy->delete($this->user, $warehouse);

    expect($result)->toBeFalse();
});

test('user can delete warehouse if has permission and owns it even with locations', function () {
    $this->user->givePermissionTo('delete_warehouses');
    $warehouse = Warehouse::factory()->forSite($this->site)->create();
    Location::factory()->create(['warehouse_id' => $warehouse->id]);

    $result = $this->policy->delete($this->user, $warehouse);

    // Policy should allow deletion if user has permission and ownership
    // Business rules (like checking for locations) should be handled in the action/controller
    expect($result)->toBeTrue();
});

test('user can restore warehouse if has permission and owns it', function () {
    $this->user->givePermissionTo('edit_warehouses');
    $warehouse = Warehouse::factory()->forSite($this->site)->create();

    $result = $this->policy->restore($this->user, $warehouse);

    expect($result)->toBeTrue();
});

test('user can force delete warehouse if has permission and owns it', function () {
    $this->user->givePermissionTo('delete_warehouses');
    $warehouse = Warehouse::factory()->forSite($this->site)->create();

    $result = $this->policy->forceDelete($this->user, $warehouse);

    expect($result)->toBeTrue();
});
