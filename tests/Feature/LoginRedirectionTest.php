<?php

use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
});

it('redirects admin users to admin dashboard after login', function () {
    $admin = User::factory()->create([
        'email' => 'admin@test.com',
        'password' => 'password',
    ]);
    $admin->assignRole('Admin');

    $response = $this->post('/login', [
        'email' => 'admin@test.com',
        'password' => 'password',
    ]);

    $response->assertRedirect('/admin/dashboard');
});

it('redirects site admin users to site dashboard after login', function () {
    $site = Site::factory()->create(['slug' => 'test-site']);
    $siteAdmin = User::factory()->create([
        'email' => 'siteadmin@test.com',
        'password' => 'password',
        'site_id' => $site->id,
    ]);
    $siteAdmin->assignRole('SiteAdmin');

    $response = $this->post('/login', [
        'email' => 'siteadmin@test.com',
        'password' => 'password',
    ]);

    $response->assertRedirect('/test-site/dashboard');
});

it('redirects users without specific roles to home page', function () {
    $user = User::factory()->create([
        'email' => 'user@test.com',
        'password' => 'password',
    ]);
    // No role assigned

    $response = $this->post('/login', [
        'email' => 'user@test.com',
        'password' => 'password',
    ]);

    $response->assertRedirect('/');
});

it('handles site admin without site gracefully', function () {
    $siteAdmin = User::factory()->create([
        'email' => 'orphan@test.com',
        'password' => 'password',
        'site_id' => null, // No site assigned
    ]);
    $siteAdmin->assignRole('SiteAdmin');

    $response = $this->post('/login', [
        'email' => 'orphan@test.com',
        'password' => 'password',
    ]);

    $response->assertRedirect('/'); // Fallback to home
});

it('fails login with invalid credentials', function () {
    $user = User::factory()->create([
        'email' => 'user@test.com',
        'password' => 'password',
    ]);

    $response = $this->post('/login', [
        'email' => 'user@test.com',
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors();
    $this->assertGuest();
});

it('requires email and password for login', function () {
    $response = $this->post('/login', []);

    $response->assertSessionHasErrors(['email', 'password']);
});

it('validates email format', function () {
    $response = $this->post('/login', [
        'email' => 'invalid-email',
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
});

it('respects intended redirect after login', function () {
    $admin = User::factory()->create([
        'email' => 'admin@test.com',
        'password' => 'password',
    ]);
    $admin->assignRole('Admin');

    // First try to access a protected page
    $this->get('/admin/dashboard');

    // Then login
    $response = $this->post('/login', [
        'email' => 'admin@test.com',
        'password' => 'password',
    ]);

    $response->assertRedirect('/admin/dashboard');
});
