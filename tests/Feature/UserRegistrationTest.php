<?php

use App\Actions\Fortify\CreateNewUser;
use App\Models\User;

test('user registration requires unique email', function () {
    $existingUser = User::factory()->create(['email' => 'test@example.com']);

    $userData = [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'phone_number' => '+1234567890',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    expect(fn () => app(CreateNewUser::class)->create($userData))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});

test('user registration requires unique phone number', function () {
    $existingUser = User::factory()->create(['phone_number' => '+1234567890']);

    $userData = [
        'name' => 'John Doe',
        'email' => 'unique@example.com',
        'phone_number' => '+1234567890',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    expect(fn () => app(CreateNewUser::class)->create($userData))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});

test('user registration succeeds with unique email and phone number', function () {
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
    $site = $user->sites()->first();
    expect($site)->not()->toBeNull()
        ->and($site->name)->toBe('My Awesome Site')
        ->and($user)->toBeInstanceOf(User::class)
        ->and($user->email)->toBe('unique@example.com')
        ->and($user->phone_number)->toBe('+1234567890')
        ->and($user->site_id)->not()->toBeNull()
        ->and($user->site->name)->toBe('My Awesome Site');
});
