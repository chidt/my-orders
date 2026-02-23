<?php

use App\Actions\Fortify\CreateNewUser;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
});

test('user and site are created in a transaction', function () {
    // Create test province and ward for required address fields
    $province = \App\Models\Province::factory()->create();
    $ward = \App\Models\Ward::factory()->create(['province_id' => $province->id]);

    $userData = [
        'name' => 'John Doe',
        'email' => 'unique@example.com',
        'phone_number' => '+1234567890',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'site_name' => 'My Awesome Site',
        'site_slug' => 'my-awesome-site',
        'address' => '123 Test St',
        'province_id' => $province->id,
        'ward_id' => $ward->id,
    ];

    $user = app(CreateNewUser::class)->create($userData);

    expect($user)->toBeInstanceOf(User::class);
    $this->assertDatabaseHas('users', ['email' => 'unique@example.com']);
    $this->assertDatabaseHas('sites', ['slug' => 'my-awesome-site']);

    // Verify role assignment
    expect($user->hasRole('SiteAdmin'))->toBeTrue();
});

test('transaction rolls back on validation failure', function () {
    // Simulate a unique constraint violation on sites table
    Site::factory()->create(['slug' => 'existing-slug']);

    // Create test province and ward for required address fields
    $province = \App\Models\Province::factory()->create();
    $ward = \App\Models\Ward::factory()->create(['province_id' => $province->id]);

    $userData = [
        'name' => 'John Doe',
        'email' => 'another@example.com',
        'phone_number' => '+9876543210',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'site_name' => 'Another Site',
        'site_slug' => 'existing-slug',
        'address' => '456 Another Ave',
        'province_id' => $province->id,
        'ward_id' => $ward->id,
    ];

    DB::beginTransaction();

    try {
        app(CreateNewUser::class)->create($userData);
    } catch (\Illuminate\Validation\ValidationException $e) {
        // Expected exception
    }

    DB::rollBack();

    $this->assertDatabaseMissing('users', ['email' => 'another@example.com']);
    $this->assertDatabaseHas('sites', ['slug' => 'existing-slug']);
});

test('user role assignment is included in transaction', function () {
    // Create test province and ward for required address fields
    $province = \App\Models\Province::factory()->create();
    $ward = \App\Models\Ward::factory()->create(['province_id' => $province->id]);

    $userData = [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'phone_number' => '+1111111111',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'site_name' => 'Test Site',
        'site_slug' => 'test-site',
        'address' => '789 Test Blvd',
        'province_id' => $province->id,
        'ward_id' => $ward->id,
    ];

    $user = app(CreateNewUser::class)->create($userData);

    expect($user->hasRole('SiteAdmin'))->toBeTrue()
        ->and($user->site)->not()->toBeNull()
        ->and($user->site->slug)->toBe('test-site');
});

test('user can access site dashboard after registration', function () {
    // Create test province and ward for required address fields
    $province = \App\Models\Province::factory()->create();
    $ward = \App\Models\Ward::factory()->create(['province_id' => $province->id]);

    $userData = [
        'name' => 'Site Owner',
        'email' => 'owner@example.com',
        'phone_number' => '+2222222222',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'site_name' => 'Owner Site',
        'site_slug' => 'owner-site',
        'address' => '101 Owner Plaza',
        'province_id' => $province->id,
        'ward_id' => $ward->id,
    ];

    $user = app(CreateNewUser::class)->create($userData);

    expect($user->hasRole('SiteAdmin'))->toBeTrue()
        ->and($user->site->slug)->toBe('owner-site')
        ->and($user->site_id)->toBe($user->site->id);

    // Verify user can be authenticated and has proper site access
    $this->actingAs($user);
    $response = $this->get("/{$user->site->slug}/dashboard");
    $response->assertStatus(200);
});
