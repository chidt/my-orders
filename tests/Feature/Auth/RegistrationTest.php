<?php

use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'phone_number' => '+1234567890',
        'password' => 'password',
        'password_confirmation' => 'password',
        'site_name' => 'My Awesome Site',
        'site_slug' => 'my-awesome-site',
    ]);

    $this->assertAuthenticated();

    // New users get SiteAdmin role and should be redirected to their site dashboard
    $user = User::where('email', 'test@example.com')->first();
    $response->assertRedirect('/'.$user->site->slug.'/dashboard');
});

// Replace all '/dashboard' with '/admin/dashboard' for admin dashboard access
// and keep '{site}/dashboard' for site dashboards.
