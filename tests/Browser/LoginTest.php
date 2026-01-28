<?php
use App\Models\User;
it('user can login', function () {
    User::factory()->create([ // assumes RefreshDatabase trait is used on Pest.php...
        'email' => 'chidt@test.com',
        'password' => 'password',
    ]);
    $page = visit('/');
    $page->assertSee('Chào mừng bạn đến với My Orders');
    $page->click('Đăng nhập')
        ->assertPathEndsWith('/login')
        ->fill('email', 'chidt@test.com')
        ->fill('password', 'password')
        ->click('Log in')
        ->assertSee('Dashboard');
    ;


});
