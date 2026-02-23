<?php

use App\Models\Site;
use App\Models\User;

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
});

it('redirects to site dashboard after registration', function () {
    // Create test province and ward for required address fields
    $province = \App\Models\Province::factory()->create();
    $ward = \App\Models\Ward::factory()->create(['province_id' => $province->id]);

    $response = $this->post('/register', [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'phone_number' => '0123456789',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'site_name' => 'Test Store',
        'site_slug' => 'test-store',
        'site_description' => 'A test store',
        'address' => '123 Store Street',
        'province_id' => $province->id,
        'ward_id' => $ward->id,
    ]);

    // Check if user was created
    $user = User::where('email', 'test@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->hasRole('SiteAdmin'))->toBeTrue();

    // Check if site was created
    $site = $user->site;
    expect($site)->not->toBeNull();
    expect($site->slug)->toBe('test-store');

    // Check redirect
    $response->assertRedirect('/test-store/dashboard');
});
