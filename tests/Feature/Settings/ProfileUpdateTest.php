<?php

use App\Models\Address;
use App\Models\Province;
use App\Models\User;
use App\Models\Ward;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// Helper method for creating test user with address
function createTestUserWithAddress(User $user, Ward $ward): User
{
    Address::factory()->forUser($user)->create([
        'ward_id' => $ward->id,
        'address' => 'Test Address 123',
    ]);

    return $user;
}

// Helper method for valid address data
function getValidAddressData(Province $province, Ward $ward): array
{
    return [
        'address' => '123 Test Street',
        'province_id' => $province->id,
        'ward_id' => $ward->id,
    ];
}

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('profile.edit'));

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    // Create test address data
    $testProvince = Province::factory()->create();
    $testWard = Ward::factory()->create(['province_id' => $testProvince->id]);

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'address' => '123 Test Street',
            'province_id' => $testProvince->id,
            'ward_id' => $testWard->id,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->name)->toBe('Test User')
        ->and($user->email)->toBe('test@example.com')
        ->and($user->email_verified_at)->toBeNull();
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    // Create test address data
    $testProvince = Province::factory()->create();
    $testWard = Ward::factory()->create(['province_id' => $testProvince->id]);

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => $user->email,
            'address' => '123 Test Street',
            'province_id' => $testProvince->id,
            'ward_id' => $testWard->id,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('profile.destroy'), [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('home'));

    $this->assertGuest();
    expect($user->fresh())->toBeNull();
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->delete(route('profile.destroy'), [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrors('password')
        ->assertRedirect(route('profile.edit'));

    expect($user->fresh())->not->toBeNull();
});

test('user can update existing address with valid data', function () {
    // Create test data
    $testProvince = Province::factory()->create();
    $testWard = Ward::factory()->create(['province_id' => $testProvince->id]);
    $user = User::factory()->create();
    createTestUserWithAddress($user, $testWard);

    $addressData = getValidAddressData($testProvince, $testWard);
    $addressData['address'] = 'Updated Address 456';

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), array_merge(
            ['name' => 'Test User', 'email' => 'test@example.com'],
            $addressData
        ));

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $this->assertDatabaseHas('addresses', [
        'addressable_type' => User::class,
        'addressable_id' => $user->id,
        'address' => 'Updated Address 456',
        'ward_id' => $testWard->id,
    ]);
});

test('user can create address when none exists', function () {
    // Create test data
    $testProvince = Province::factory()->create();
    $testWard = Ward::factory()->create(['province_id' => $testProvince->id]);
    $user = User::factory()->create(); // No address initially

    $addressData = getValidAddressData($testProvince, $testWard);

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), array_merge(
            ['name' => 'Test User', 'email' => 'test@example.com'],
            $addressData
        ));

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->addresses()->count())->toBe(1);
    expect($user->addresses()->first()->address)->toBe('123 Test Street');
    expect($user->addresses()->first()->ward_id)->toBe($testWard->id);
});

test('address validation fails when address is missing', function () {
    // Create test data
    $testProvince = Province::factory()->create();
    $testWard = Ward::factory()->create(['province_id' => $testProvince->id]);
    $user = User::factory()->create();
    createTestUserWithAddress($user, $testWard);

    $invalidData = getValidAddressData($testProvince, $testWard);
    unset($invalidData['address']);

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), array_merge(
            ['name' => 'Test User', 'email' => 'test@example.com'],
            $invalidData
        ));

    $response->assertSessionHasErrors('address');
});

test('address validation fails when address is too long', function () {
    // Create test data
    $testProvince = Province::factory()->create();
    $testWard = Ward::factory()->create(['province_id' => $testProvince->id]);
    $user = User::factory()->create();
    createTestUserWithAddress($user, $testWard);

    $invalidData = getValidAddressData($testProvince, $testWard);
    $invalidData['address'] = str_repeat('a', 256); // 256 characters

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), array_merge(
            ['name' => 'Test User', 'email' => 'test@example.com'],
            $invalidData
        ));

    $response->assertSessionHasErrors('address');
});

test('address validation fails when province_id is missing', function () {
    // Create test data
    $testProvince = Province::factory()->create();
    $testWard = Ward::factory()->create(['province_id' => $testProvince->id]);
    $user = User::factory()->create();
    createTestUserWithAddress($user, $testWard);

    $invalidData = getValidAddressData($testProvince, $testWard);
    unset($invalidData['province_id']);

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), array_merge(
            ['name' => 'Test User', 'email' => 'test@example.com'],
            $invalidData
        ));

    $response->assertSessionHasErrors('province_id');
});

test('address validation fails when province_id does not exist', function () {
    // Create test data
    $testProvince = Province::factory()->create();
    $testWard = Ward::factory()->create(['province_id' => $testProvince->id]);
    $user = User::factory()->create();
    createTestUserWithAddress($user, $testWard);

    $invalidData = getValidAddressData($testProvince, $testWard);
    $invalidData['province_id'] = 99999; // Non-existent province

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), array_merge(
            ['name' => 'Test User', 'email' => 'test@example.com'],
            $invalidData
        ));

    $response->assertSessionHasErrors('province_id');
});

test('address validation fails when ward_id is missing', function () {
    // Create test data
    $testProvince = Province::factory()->create();
    $testWard = Ward::factory()->create(['province_id' => $testProvince->id]);
    $user = User::factory()->create();
    createTestUserWithAddress($user, $testWard);

    $invalidData = getValidAddressData($testProvince, $testWard);
    unset($invalidData['ward_id']);

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), array_merge(
            ['name' => 'Test User', 'email' => 'test@example.com'],
            $invalidData
        ));

    $response->assertSessionHasErrors('ward_id');
});

test('address validation fails when ward_id does not exist', function () {
    // Create test data
    $testProvince = Province::factory()->create();
    $testWard = Ward::factory()->create(['province_id' => $testProvince->id]);
    $user = User::factory()->create();
    createTestUserWithAddress($user, $testWard);

    $invalidData = getValidAddressData($testProvince, $testWard);
    $invalidData['ward_id'] = 99999; // Non-existent ward

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), array_merge(
            ['name' => 'Test User', 'email' => 'test@example.com'],
            $invalidData
        ));

    $response->assertSessionHasErrors('ward_id');
});

test('address validation fails when ward does not belong to selected province', function () {
    // Create test data
    $testProvince = Province::factory()->create();
    $testWard = Ward::factory()->create(['province_id' => $testProvince->id]);
    $otherProvince = Province::factory()->create();
    $otherWard = Ward::factory()->create(['province_id' => $otherProvince->id]);
    $user = User::factory()->create();
    createTestUserWithAddress($user, $testWard);

    $invalidData = [
        'address' => '123 Test Street',
        'province_id' => $testProvince->id,
        'ward_id' => $otherWard->id, // Ward from different province
    ];

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), array_merge(
            ['name' => 'Test User', 'email' => 'test@example.com'],
            $invalidData
        ));

    $response->assertSessionHasErrors('ward_id');
    expect(session('errors')->get('ward_id')[0])
        ->toBe('The selected ward does not belong to the selected province.');
});

test('address validation passes when ward belongs to selected province', function () {
    // Create test data
    $testProvince = Province::factory()->create();
    $testWard = Ward::factory()->create(['province_id' => $testProvince->id]);
    $user = User::factory()->create();
    createTestUserWithAddress($user, $testWard);

    $validData = [
        'address' => '123 Test Street',
        'province_id' => $testProvince->id,
        'ward_id' => $testWard->id, // Ward from same province
    ];

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), array_merge(
            ['name' => 'Test User', 'email' => 'test@example.com'],
            $validData
        ));

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $this->assertDatabaseHas('addresses', [
        'addressable_type' => User::class,
        'addressable_id' => $user->id,
        'address' => '123 Test Street',
        'ward_id' => $testWard->id,
    ]);
});

test('address validation fails with non-string address', function () {
    // Create test data
    $testProvince = Province::factory()->create();
    $testWard = Ward::factory()->create(['province_id' => $testProvince->id]);
    $user = User::factory()->create();
    createTestUserWithAddress($user, $testWard);

    $invalidData = [
        'address' => 12345, // Integer instead of string
        'province_id' => $testProvince->id,
        'ward_id' => $testWard->id,
    ];

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), array_merge(
            ['name' => 'Test User', 'email' => 'test@example.com'],
            $invalidData
        ));

    $response->assertSessionHasErrors('address');
});

test('address validation fails with non-integer province_id', function () {
    // Create test data
    $testProvince = Province::factory()->create();
    $testWard = Ward::factory()->create(['province_id' => $testProvince->id]);
    $user = User::factory()->create();
    createTestUserWithAddress($user, $testWard);

    $invalidData = [
        'address' => '123 Test Street',
        'province_id' => 'not-an-integer',
        'ward_id' => $testWard->id,
    ];

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), array_merge(
            ['name' => 'Test User', 'email' => 'test@example.com'],
            $invalidData
        ));

    $response->assertSessionHasErrors('province_id');
});

test('address validation fails with non-integer ward_id', function () {
    // Create test data
    $testProvince = Province::factory()->create();
    $testWard = Ward::factory()->create(['province_id' => $testProvince->id]);
    $user = User::factory()->create();
    createTestUserWithAddress($user, $testWard);

    $invalidData = [
        'address' => '123 Test Street',
        'province_id' => $testProvince->id,
        'ward_id' => 'not-an-integer',
    ];

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), array_merge(
            ['name' => 'Test User', 'email' => 'test@example.com'],
            $invalidData
        ));

    $response->assertSessionHasErrors('ward_id');
});
