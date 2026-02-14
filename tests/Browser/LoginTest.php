<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
});

it('user can login', function () {
    $user = User::factory()->create([
        'email' => 'chidt@test.com',
        'password' => 'password',
    ]);
    $user->assignRole('Admin'); // Assign admin role for dashboard access

    $page = visit('/');

    $page->assertSee('Chào mừng bạn đến với My Orders');
    $page->click('Đăng nhập')
        ->assertPathEndsWith('/login')
        ->fill('email', 'chidt@test.com')
        ->fill('password', 'password')
        ->click('Đăng nhập')
        ->assertPathEndsWith('/admin/dashboard')
        ->assertSee('Dashboard');

});
