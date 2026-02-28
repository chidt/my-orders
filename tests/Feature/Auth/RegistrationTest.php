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
    // Create test province and ward
    $province = \App\Models\Province::factory()->create();
    $ward = \App\Models\Ward::factory()->create(['province_id' => $province->id]);

    $response = $this->post(route('register.store'), [
        'name' => 'Test User With Address',
        'email' => 'testwithaddress@example.com',
        'phone_number' => '+1234567891',
        'password' => 'password',
        'password_confirmation' => 'password',
        'site_name' => 'My Site With Address',
        'site_slug' => 'my-site-address',
        'address' => '123 Test Street',
        'province_id' => $province->id,
        'ward_id' => $ward->id,
    ]);

    $response->assertStatus(302);
    $this->assertAuthenticated();

    $user = User::where('email', 'testwithaddress@example.com')->first();
    $this->assertNotNull($user);
    $this->assertNotNull($user->site);
    $this->assertEquals('My Site With Address', $user->site->name);
    $this->assertEquals('my-site-address', $user->site->slug);

    // Check address was created correctly
    $address = $user->addresses()->first();
    $this->assertNotNull($address);
    $this->assertEquals('123 Test Street', $address->address);
    $this->assertEquals($ward->id, $address->ward_id);

    $response->assertRedirect('/'.$user->site->slug.'/dashboard');
});

// Replace all '/dashboard' with '/admin/dashboard' for admin dashboard access
// and keep '{site}/dashboard' for site dashboards.
