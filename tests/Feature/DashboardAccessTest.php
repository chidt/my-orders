<?php

use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
});

it('allows admin users to access admin dashboard', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $response = $this->actingAs($admin)->get('/admin/dashboard');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('Admin/Dashboard')
        ->has('stats')
        ->has('recent_users')
        ->has('recent_sites')
    );
});

it('denies non-admin users access to admin dashboard', function () {
    $site = Site::factory()->create();
    $siteAdmin = User::factory()->create(['site_id' => $site->id]);
    $siteAdmin->assignRole('SiteAdmin');

    $response = $this->actingAs($siteAdmin)->get('/admin/dashboard');

    $response->assertStatus(403);
});

it('allows site admin to access their site dashboard', function () {
    $site = Site::factory()->create(['slug' => 'test-site']);
    $siteAdmin = User::factory()->create(['site_id' => $site->id]);
    $siteAdmin->assignRole('SiteAdmin');

    $response = $this->actingAs($siteAdmin)->get('/test-site/dashboard');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('Site/Dashboard')
        ->has('site')
        ->has('stats')
        ->where('site.slug', 'test-site')
    );
});

it('denies site admin access to other site dashboards', function () {
    $site1 = Site::factory()->create(['slug' => 'site-1']);
    $site2 = Site::factory()->create(['slug' => 'site-2']);

    $siteAdmin = User::factory()->create(['site_id' => $site1->id]);
    $siteAdmin->assignRole('SiteAdmin');

    $response = $this->actingAs($siteAdmin)->get('/site-2/dashboard');

    $response->assertStatus(403);
});

it('denies non-site-admin users access to site dashboard', function () {
    $site = Site::factory()->create(['slug' => 'test-site']);
    $admin = User::factory()->withoutSite()->create();
    $admin->assignRole('Admin');

    $response = $this->actingAs($admin)->get('/test-site/dashboard');

    $response->assertStatus(403);
});

it('redirects unauthenticated users to login', function () {
    $response = $this->get('/admin/dashboard');

    $response->assertRedirect('/login');
});

it('redirects unauthenticated users from site dashboard to login', function () {
    Site::factory()->create(['slug' => 'test-site']);

    $response = $this->get('/test-site/dashboard');

    $response->assertRedirect('/login');
});

it('returns 404 for non-existent site dashboard', function () {
    $siteAdmin = User::factory()->create();
    $siteAdmin->assignRole('SiteAdmin');

    $response = $this->actingAs($siteAdmin)->get('/non-existent-site/dashboard');

    $response->assertStatus(404);
});

it('admin dashboard shows correct statistics', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    // Create some test data
    User::factory()->count(3)->create();
    Site::factory()->count(2)->create();

    $response = $this->actingAs($admin)->get('/admin/dashboard');

    $response->assertInertia(fn ($page) => $page->where('stats.total_users', User::count()) // Use actual count
        ->where('stats.total_sites', 2)
        ->where('stats.admin_users', 1)
    );
});

it('site dashboard shows correct site information', function () {
    $site = Site::factory()->create([
        'slug' => 'test-site',
        'name' => 'Test Site',
        'description' => 'Test Description',
    ]);

    $siteAdmin = User::factory()->create(['site_id' => $site->id]);
    $siteAdmin->assignRole('SiteAdmin');

    $response = $this->actingAs($siteAdmin)->get('/test-site/dashboard');

    $response->assertInertia(fn ($page) => $page->where('site.name', 'Test Site')
        ->where('site.slug', 'test-site')
        ->where('site.description', 'Test Description')
    );
});

it('handles site admin with no assigned site', function () {
    $siteAdmin = User::factory()->create(['site_id' => null]);
    $siteAdmin->assignRole('SiteAdmin');

    Site::factory()->create(['slug' => 'some-site']);

    $response = $this->actingAs($siteAdmin)->get('/some-site/dashboard');

    $response->assertStatus(403);
});
