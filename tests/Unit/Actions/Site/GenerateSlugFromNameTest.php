<?php

use App\Actions\Site\GenerateSlugFromName;
use Tests\TestCase;

uses(TestCase::class);

test('it generates slug from name correctly', function () {
    $action = new GenerateSlugFromName;

    expect($action->handle('My Awesome Site'))->toBe('my-awesome-site')
        ->and($action->handle('Test Site 123'))->toBe('test-site-123')
        ->and($action->handle('UPPERCASE NAME'))->toBe('uppercase-name');
});

test('it handles names starting with numbers', function () {
    $action = new GenerateSlugFromName;

    expect($action->handle('123 Test Site'))->toBe('123-test-site');
    expect($action->handle('999 Store'))->toBe('999-store');
});

test('it handles names starting with dashes', function () {
    $action = new GenerateSlugFromName;

    expect($action->handle('-Test Site'))->toBe('test-site');
    expect($action->handle('--Multiple Dashes'))->toBe('multiple-dashes');
});

test('it limits slug length to 50 characters', function () {
    $action = new GenerateSlugFromName;
    $longName = 'This is a very long site name that should be truncated to fit within the slug limit of fifty characters';

    $slug = $action->handle($longName);

    expect(strlen($slug))->toBeLessThanOrEqual(50)
        ->and($slug)->toStartWith('this-is-a-very-long-site-name');
    // Just check that it's properly truncated, don't hardcode the exact result
});

test('it has method to check unique slugs', function () {
    $action = new GenerateSlugFromName;

    // Test that the methods exist
    expect(method_exists($action, 'handleUnique'))->toBeTrue();
    expect(method_exists($action, 'handle'))->toBeTrue();

    // Test basic functionality without database dependency
    $basicSlug = $action->handle('Test Site');
    expect($basicSlug)->toBeString()
        ->and($basicSlug)->toBe('test-site');
});
