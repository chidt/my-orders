<?php

use App\Models\Customer;
use App\Models\Site;
use App\Models\User;
use App\Policies\CustomerPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(\Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->policy = new CustomerPolicy;

    Permission::findOrCreate('manage_customers', 'web');
    Permission::findOrCreate('view_customers', 'web');
    Permission::findOrCreate('create_customers', 'web');
    Permission::findOrCreate('edit_customers', 'web');
    Permission::findOrCreate('delete_customers', 'web');
});

it('allows owner with manage_customers permission to view any for own site', function (): void {
    $owner = User::factory()->create();
    $site = Site::factory()->create(['user_id' => $owner->id]);

    $owner->givePermissionTo('manage_customers');

    expect($this->policy->viewAny($owner, $site))->toBeTrue();
});

it('denies view any when user does not own site', function (): void {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $site = Site::factory()->create(['user_id' => $owner->id]);

    $otherUser->givePermissionTo('manage_customers');

    expect($this->policy->viewAny($otherUser, $site))->toBeFalse();
});

it('allows owner with view_customers permission to view customer', function (): void {
    $owner = User::factory()->create();
    $site = Site::factory()->create(['user_id' => $owner->id]);
    $customer = Customer::factory()->forSite($site)->create();

    $owner->givePermissionTo('view_customers');

    expect($this->policy->view($owner, $customer))->toBeTrue();
});

it('denies update when user lacks edit permission', function (): void {
    $owner = User::factory()->create();
    $site = Site::factory()->create(['user_id' => $owner->id]);
    $customer = Customer::factory()->forSite($site)->create();

    expect($this->policy->update($owner, $customer))->toBeFalse();
});

it('allows delete only for owner with delete permission', function (): void {
    $owner = User::factory()->create();
    $site = Site::factory()->create(['user_id' => $owner->id]);
    $customer = Customer::factory()->forSite($site)->create();

    $owner->givePermissionTo('delete_customers');

    expect($this->policy->delete($owner, $customer))->toBeTrue();
});
