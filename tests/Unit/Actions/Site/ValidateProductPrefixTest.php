<?php

use App\Actions\Site\ValidateProductPrefix;
use Tests\TestCase;

uses(TestCase::class);

test('it validates and formats product prefix correctly', function () {
    $action = new ValidateProductPrefix;

    expect($action->handle('abc'))->toBe('ABC')
        ->and($action->handle('123'))->toBe('123')
        ->and($action->handle('A1B2C'))->toBe('A1B2C')
        ->and($action->handle('A1B2C3D4E5F'))->toBe('A1B2C');
    // Truncated to 5 chars
});

test('it removes invalid characters', function () {
    $action = new ValidateProductPrefix;

    expect($action->handle('a-b_c!'))->toBe('ABC')
        ->and($action->handle('1@2#3$'))->toBe('123')
        ->and($action->handle('test-123'))->toBe('TEST1');
});

test('it handles null and empty values', function () {
    $action = new ValidateProductPrefix;

    expect($action->handle(null))->toBeNull()
        ->and($action->handle(''))->toBeNull()
        ->and($action->handle('   '))->toBeNull();
});

test('it generates correct examples', function () {
    $action = new ValidateProductPrefix;

    expect($action->generateExample('A'))->toBe('A001')
        ->and($action->generateExample('TEST'))->toBe('TEST001')
        ->and($action->generateExample(''))->toBe('001')
        ->and($action->generateExample(null))->toBe('001');
});

test('it limits prefix to 5 characters', function () {
    $action = new ValidateProductPrefix;

    expect($action->handle('VERYLONGPREFIX'))->toBe('VERYL')
        ->and(strlen($action->handle('VERYLONGPREFIX')))->toBe(5);
});
