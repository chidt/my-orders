<?php

use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
});

test('admin user without site gets 404', function () {
    $adminUser = User::factory()->create();

    // Admin role has manage_own_site permission but user has no site
    $adminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Admin']);
    $adminUser->assignRole($adminRole);

    $this->actingAs($adminUser);
    $response = $this->actingAs($adminUser)->get(route('site.edit'));
    $response->assertStatus(403);
});

test('site admin can only manage their own site', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $site1 = Site::factory()->create(['user_id' => $user1->id]);
    $site2 = Site::factory()->create(['user_id' => $user2->id]);

    $user1->update(['site_id' => $site1->id]);
    $user2->update(['site_id' => $site2->id]);

    // Create SiteAdmin role and manage_own_site permission
    $siteAdminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'SiteAdmin']);
    $manageOwnSitePermission = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'manage_own_site']);

    // Assign permission to role
    $siteAdminRole->givePermissionTo($manageOwnSitePermission);

    // Assign role to users
    $user1->assignRole($siteAdminRole);
    $user2->assignRole($siteAdminRole);

    $this->actingAs($user1);
    $page = visit('/'.$site1->slug.'/dashboard');
    $page->assertSee($site1->name);

    $this->actingAs($user2);
    $page = visit('/'.$site2->slug.'/dashboard');
    $page->assertSee($site2->name);
});
