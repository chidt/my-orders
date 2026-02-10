<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
});

it('user can register', function () {
    $province = \App\Models\Province::factory()->create();
    $ward = \App\Models\Ward::factory()->create(['province_id' => $province->id]);
    $page = visit('/register');

    // Fill out the registration form
    $page->fill('name', 'John Doe')
        ->fill('email', 'unique@example.com')
        ->fill('phone_number', '0123456789')
        ->fill('password', 'password123')
        ->fill('password_confirmation', 'password123')
        ->fill('site_name', 'Demo Store')
        ->fill('site_slug', 'demo-store')
        ->fill('site_description', 'Demo store for browser test')
        ->fill('address', '123 Test Street')
        ->fill('province_id', $province->id)
        ->fill('ward_id', $ward->id);

    // Submit the form using data-test attribute
    $page->click('@register-user-button');

    // Verify successful redirect to site dashboard
    $page->assertPathEndsWith('/demo-store/dashboard')
        ->assertSee('Demo Store');

    // Verify user and site were created correctly
    $user = \App\Models\User::where('email', 'unique@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->hasRole('SiteAdmin'))->toBeTrue();

    $site = $user->site;
    expect($site)->not->toBeNull()
        ->and($site->slug)->toBe('demo-store');
});
