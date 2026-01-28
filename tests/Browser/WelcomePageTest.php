<?php
it('Welcome page test', function () {
    $page = visit('/');

    $page->assertSee('Chào mừng bạn đến với My Orders');
});
