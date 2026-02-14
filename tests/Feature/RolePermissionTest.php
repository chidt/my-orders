<?php

use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
});

it('creates roles successfully', function () {
    expect(Role::where('name', 'Admin')->exists())->toBeTrue()
        ->and(Role::where('name', 'SiteAdmin')->exists())->toBeTrue();
});

it('creates permissions successfully', function () {
    $expectedPermissions = [
        'manage_users',
        'manage_sites',
        'view_admin_dashboard',
        'manage_own_site',
        'view_site_dashboard',
    ];

    foreach ($expectedPermissions as $permission) {
        expect(Permission::where('name', $permission)->exists())->toBeTrue();
    }
});

it('assigns permissions to admin role correctly', function () {
    $adminRole = Role::where('name', 'Admin')->first();

    expect($adminRole->hasPermissionTo('manage_users'))->toBeTrue()
        ->and($adminRole->hasPermissionTo('manage_sites'))->toBeTrue()
        ->and($adminRole->hasPermissionTo('view_admin_dashboard'))->toBeTrue();
});

it('assigns permissions to SiteAdmin role correctly', function () {
    $siteAdminRole = Role::where('name', 'SiteAdmin')->first();

    expect($siteAdminRole->hasPermissionTo('manage_own_site'))->toBeTrue();
    expect($siteAdminRole->hasPermissionTo('view_site_dashboard'))->toBeTrue();
});

it('can assign admin role to user', function () {
    $user = User::factory()->create();
    $user->assignRole('Admin');

    expect($user->hasRole('Admin'))->toBeTrue();
    expect($user->hasPermissionTo('manage_users'))->toBeTrue();
    expect($user->hasPermissionTo('view_admin_dashboard'))->toBeTrue();
});

it('can assign SiteAdmin role to user', function () {
    $site = Site::factory()->create();
    $user = User::factory()->create(['site_id' => $site->id]);
    $user->assignRole('SiteAdmin');

    expect($user->hasRole('SiteAdmin'))->toBeTrue()
        ->and($user->hasPermissionTo('manage_own_site'))->toBeTrue()
        ->and($user->hasPermissionTo('view_site_dashboard'))->toBeTrue();
});

it('allows multiple roles assignment', function () {
    $user = User::factory()->create();
    $user->assignRole(['Admin', 'SiteAdmin']);

    expect($user->hasRole('Admin'))->toBeTrue()
        ->and($user->hasRole('SiteAdmin'))->toBeTrue();
});

it('prevents duplicate role creation', function () {
    // Run seeder twice
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    expect(Role::where('name', 'Admin')->count())->toBe(1)
        ->and(Role::where('name', 'SiteAdmin')->count())->toBe(1);
});

it('prevents duplicate permission creation', function () {
    // Run seeder twice
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    expect(Permission::where('name', 'manage_users')->count())->toBe(1)
        ->and(Permission::where('name', 'view_admin_dashboard')->count())->toBe(1);
});
