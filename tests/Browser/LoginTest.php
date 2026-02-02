<?php

use App\Models\User;

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
});

it('user can login', function () {
    $user = User::factory()->create([
        'email' => 'chidt@test.com',
        'password' => 'password',
    ]);
    $user->assignRole('admin'); // Assign admin role for dashboard access

    $page = visit('/');

    $page->assertSee('Chào mừng bạn đến với My Orders');
    $page->click('Đăng nhập')
        ->assertPathEndsWith('/login')
        ->fill('email', 'chidt@test.com')
        ->fill('password', 'password')
        ->click('Đăng nhập')
        ->assertSee('Dashboard');

});
