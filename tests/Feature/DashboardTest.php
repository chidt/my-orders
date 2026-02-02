<?php

use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
});

test('guests are redirected to the login page', function () {
    $response = $this->get(route('admin.dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated admin users can visit the dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole('admin'); // Admin role required for dashboard access
    $this->actingAs($user);

    $response = $this->get(route('admin.dashboard'));
    $response->assertOk();
});

test('non-admin users cannot visit the dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole('SiteAdmin'); // SiteAdmin users cannot access admin dashboard
    $this->actingAs($user);

    $response = $this->get(route('admin.dashboard'));
    $response->assertStatus(403);
});
