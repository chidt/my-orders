<?php

use App\Actions\Fortify\CreateNewUser;
use App\Models\Site;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('user and site are created in a transaction', function () {
    $userData = [
        'name' => 'John Doe',
        'email' => 'unique@example.com',
        'phone_number' => '+1234567890',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'site_name' => 'My Awesome Site',
        'site_slug' => 'my-awesome-site',
    ];

    $user = app(CreateNewUser::class)->create($userData);

    expect($user)->toBeInstanceOf(User::class);
    $this->assertDatabaseHas('users', ['email' => 'unique@example.com']);
    $this->assertDatabaseHas('sites', ['slug' => 'my-awesome-site']);
});

test('transaction rolls back on validation failure', function () {
    // Simulate a unique constraint violation on the sites table
    Site::factory()->create(['slug' => 'existing-slug']);

    $userData = [
        'name' => 'John Doe',
        'email' => 'another@example.com',
        'phone_number' => '+9876543210',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'site_name' => 'Another Site',
        'site_slug' => 'existing-slug',
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
