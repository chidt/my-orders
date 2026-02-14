<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
});

it('user can register', function () {
    $page = visit('/register');

    // Fill out the registration form
    $page->fill('name', 'John Doe')
        ->fill('email', 'unique@example.com')
        ->fill('phone_number', '0123456789')
        ->fill('password', 'password123')
        ->fill('password_confirmation', 'password123')
        ->fill('site_name', 'Demo Store')
        ->fill('site_slug', 'demo-store')
        ->fill('site_description', 'Demo store for browser test');

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
