<?php
it('Welcome page test', function () {
    $page = visit('/');

    // Check for our Vietnamese welcome text
    $page->assertSee('Chào mừng bạn đến với My Orders');
});
