<?php

use App\Models\Location;
use App\Models\Site;
use App\Models\User;
use App\Models\Warehouse;
use App\Policies\LocationPolicy;

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    $this->policy = new LocationPolicy;

    $this->user = User::factory()->create();
    $this->user->assignRole('SiteAdmin');
    $this->site = Site::factory()->create(['user_id' => $this->user->id]);
    $this->warehouse = Warehouse::factory()->for($this->site)->create();
    $this->location = Location::factory()->for($this->warehouse)->create();

    $this->otherSite = Site::factory()->create();
    $this->otherWarehouse = Warehouse::factory()->for($this->otherSite)->create();
    $this->otherLocation = Location::factory()->for($this->otherWarehouse)->create();
});

it('allows user to view any locations if they have permission and own warehouse', function () {
    expect($this->policy->viewAny($this->user, $this->warehouse))->toBeTrue();
});

it('denies user to view any locations if they do not own warehouse', function () {
    expect($this->policy->viewAny($this->user, $this->otherWarehouse))->toBeFalse();
});

it('allows user to view location if they have permission and own location', function () {
    expect($this->policy->view($this->user, $this->location))->toBeTrue();
});

it('denies user to view location if they do not own location', function () {
    expect($this->policy->view($this->user, $this->otherLocation))->toBeFalse();
});

it('allows user to create location if they have permission and own warehouse', function () {
    expect($this->policy->create($this->user, $this->warehouse))->toBeTrue();
});

it('denies user to create location if they do not own warehouse', function () {
    expect($this->policy->create($this->user, $this->otherWarehouse))->toBeFalse();
});

it('allows user to update location if they have permission and own location', function () {
    expect($this->policy->update($this->user, $this->location))->toBeTrue();
});

it('denies user to update location if they do not own location', function () {
    expect($this->policy->update($this->user, $this->otherLocation))->toBeFalse();
});

it('allows user to delete location if they have permission and own location', function () {
    expect($this->policy->delete($this->user, $this->location))->toBeTrue();
});

it('denies user to delete location if they do not own location', function () {
    expect($this->policy->delete($this->user, $this->otherLocation))->toBeFalse();
});

it('checks warehouse ownership correctly', function () {
    // User owns warehouse through site
    expect($this->policy->view($this->user, $this->location))->toBeTrue();

    // User does not own warehouse
    expect($this->policy->view($this->user, $this->otherLocation))->toBeFalse();
});

it('handles location without warehouse gracefully', function () {
    $orphanLocation = Location::factory()->make(['warehouse_id' => null]);
    expect($this->policy->view($this->user, $orphanLocation))->toBeFalse();
});

it('handles warehouse without site gracefully', function () {
    $orphanWarehouse = Warehouse::factory()->make(['site_id' => null]);
    $orphanLocation = Location::factory()->for($orphanWarehouse)->make();
    expect($this->policy->view($this->user, $orphanLocation))->toBeFalse();
});
