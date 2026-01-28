<?php
it('Smoke test all public url', function () {
    $routes = ['/','/login','/register','/forgot-password'];

    visit($routes)->assertNoSmoke();
});
