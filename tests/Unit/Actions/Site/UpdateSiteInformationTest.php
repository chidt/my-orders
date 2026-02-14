<?php

use App\Actions\Site\UpdateSiteInformation;

test('it has handle method', function () {
    $action = new UpdateSiteInformation;

    expect(method_exists($action, 'handle'))->toBeTrue();
});

test('it accepts correct parameters', function () {
    $action = new UpdateSiteInformation;

    $reflection = new \ReflectionClass($action);
    $method = $reflection->getMethod('handle');

    expect($method->getNumberOfParameters())->toBe(2);

    $params = $method->getParameters();
    expect($params[0]->getName())->toBe('site');
    expect($params[1]->getName())->toBe('validatedData');
});
