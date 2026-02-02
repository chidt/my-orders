<?php

use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
});

it('creates roles successfully', function () {
    expect(Role::where('name', 'admin')->exists())->toBeTrue()
        ->and(Role::where('name', 'SiteAdmin')->exists())->toBeTrue();
});

it('creates permissions successfully', function () {
    $expectedPermissions = [
        'manage-users',
        'manage-sites',
        'view-admin-dashboard',
        'manage-own-site',
        'view-site-dashboard',
    ];

    foreach ($expectedPermissions as $permission) {
        expect(Permission::where('name', $permission)->exists())->toBeTrue();
    }
});

it('assigns permissions to admin role correctly', function () {
    $adminRole = Role::where('name', 'admin')->first();

    expect($adminRole->hasPermissionTo('manage-users'))->toBeTrue();
    expect($adminRole->hasPermissionTo('manage-sites'))->toBeTrue();
    expect($adminRole->hasPermissionTo('view-admin-dashboard'))->toBeTrue();
});

it('assigns permissions to SiteAdmin role correctly', function () {
    $siteAdminRole = Role::where('name', 'SiteAdmin')->first();

    expect($siteAdminRole->hasPermissionTo('manage-own-site'))->toBeTrue();
    expect($siteAdminRole->hasPermissionTo('view-site-dashboard'))->toBeTrue();
});

it('can assign admin role to user', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    expect($user->hasRole('admin'))->toBeTrue();
    expect($user->hasPermissionTo('manage-users'))->toBeTrue();
    expect($user->hasPermissionTo('view-admin-dashboard'))->toBeTrue();
});

it('can assign SiteAdmin role to user', function () {
    $site = Site::factory()->create();
    $user = User::factory()->create(['site_id' => $site->id]);
    $user->assignRole('SiteAdmin');

    expect($user->hasRole('SiteAdmin'))->toBeTrue();
    expect($user->hasPermissionTo('manage-own-site'))->toBeTrue();
    expect($user->hasPermissionTo('view-site-dashboard'))->toBeTrue();
});

it('allows multiple roles assignment', function () {
    $user = User::factory()->create();
    $user->assignRole(['admin', 'SiteAdmin']);

    expect($user->hasRole('admin'))->toBeTrue();
    expect($user->hasRole('SiteAdmin'))->toBeTrue();
});

it('prevents duplicate role creation', function () {
    // Run seeder twice
    $this->seed(\Database\Seeders\RoleSeeder::class);

    expect(Role::where('name', 'admin')->count())->toBe(1);
    expect(Role::where('name', 'SiteAdmin')->count())->toBe(1);
});

it('prevents duplicate permission creation', function () {
    // Run seeder twice
    $this->seed(\Database\Seeders\RoleSeeder::class);

    expect(Permission::where('name', 'manage-users')->count())->toBe(1);
    expect(Permission::where('name', 'view-admin-dashboard')->count())->toBe(1);
});
