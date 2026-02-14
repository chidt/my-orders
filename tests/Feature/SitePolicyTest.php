<?php

use App\Models\Site;
use App\Models\User;
use App\Policies\SitePolicy;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    // Create the permission if it doesn't exist
    Permission::firstOrCreate(['name' => 'manage_own_site']);
});

test('user can update own site', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['user_id' => $user->id]);

    // Give user the permission
    $user->givePermissionTo('manage_own_site');

    $policy = new SitePolicy;

    expect($policy->update($user, $site))->toBeTrue();
});

test('user cannot update other users site', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $site = Site::factory()->create(['user_id' => $otherUser->id]);

    // Give user the permission
    $user->givePermissionTo('manage_own_site');

    $policy = new SitePolicy;

    expect($policy->update($user, $site))->toBeFalse();
});

test('user with manage_own_site permission can view any sites', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage_own_site');

    $policy = new SitePolicy;

    expect($policy->viewAny($user))->toBeTrue();
});

test('user without manage_own_site permission cannot view any sites', function () {
    $user = User::factory()->create();

    $policy = new SitePolicy;

    expect($policy->viewAny($user))->toBeFalse();
});

test('user can view their own site with permission', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['user_id' => $user->id]);

    $user->givePermissionTo('manage_own_site');

    $policy = new SitePolicy;

    expect($policy->view($user, $site))->toBeTrue();
});

test('user cannot view other users site even with permission', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $site = Site::factory()->create(['user_id' => $otherUser->id]);

    $user->givePermissionTo('manage_own_site');

    $policy = new SitePolicy;

    expect($policy->view($user, $site))->toBeFalse();
});
