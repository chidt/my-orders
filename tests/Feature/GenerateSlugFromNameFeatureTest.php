<?php

use App\Actions\Site\GenerateSlugFromName;
use App\Models\Site;
use App\Models\User;

test('it has method to check unique slugs', function () {
    $action = new GenerateSlugFromName;

    // Test that the method exists and returns a string
    expect(method_exists($action, 'handleUnique'))->toBeTrue();

    // Test basic functionality with database dependency
    $slug = $action->handleUnique('Test Site');
    expect($slug)->toBeString()
        ->and($slug)->toBe('test-site');
});

test('it generates unique slugs when conflicts exist in database', function () {
    $user = User::factory()->create();

    // Create existing site with slug 'test-site'
    Site::factory()->create([
        'user_id' => $user->id,
        'slug' => 'test-site',
    ]);

    $action = new GenerateSlugFromName;

    $uniqueSlug = $action->handleUnique('Test Site');

    expect($uniqueSlug)->toBe('test-site-1');
});

test('it generates incrementing unique slugs in database', function () {
    $user = User::factory()->create();

    // Create existing sites
    Site::factory()->create(['user_id' => $user->id, 'slug' => 'test-site']);
    Site::factory()->create(['user_id' => $user->id, 'slug' => 'test-site-1']);
    Site::factory()->create(['user_id' => $user->id, 'slug' => 'test-site-2']);

    $action = new GenerateSlugFromName;

    $uniqueSlug = $action->handleUnique('Test Site');

    expect($uniqueSlug)->toBe('test-site-3');
});

test('it excludes current site when checking uniqueness in database', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create([
        'user_id' => $user->id,
        'slug' => 'test-site',
    ]);

    $action = new GenerateSlugFromName;

    // Should return the same slug when excluding current site
    $uniqueSlug = $action->handleUnique('Test Site', $site->id);

    expect($uniqueSlug)->toBe('test-site');
});
