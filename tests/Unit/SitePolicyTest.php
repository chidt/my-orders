<?php

use App\Policies\SitePolicy;

test('site policy has required methods', function () {
    $policy = new SitePolicy;

    expect(method_exists($policy, 'update'))->toBeTrue()
        ->and(method_exists($policy, 'view'))->toBeTrue()
        ->and(method_exists($policy, 'viewAny'))->toBeTrue();
});

test('site policy methods have correct signatures', function () {
    $policy = new SitePolicy;

    $reflection = new \ReflectionClass($policy);

    $updateMethod = $reflection->getMethod('update');
    expect($updateMethod->getNumberOfParameters())->toBe(2);

    $viewMethod = $reflection->getMethod('view');
    expect($viewMethod->getNumberOfParameters())->toBe(2);

    $viewAnyMethod = $reflection->getMethod('viewAny');
    expect($viewAnyMethod->getNumberOfParameters())->toBe(1);
});
