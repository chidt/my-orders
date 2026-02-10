<?php

use App\Actions\Site\UpdateSiteInformation;
use App\Models\Site;
use App\Models\User;
use Tests\TestCase;

uses(TestCase::class);

test('it can update site information', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create([
        'user_id' => $user->id,
        'name' => 'Old Name',
        'slug' => 'old-slug',
        'description' => 'Old description',
        'settings' => ['product_prefix' => 'OLD'],
    ]);

    $action = new UpdateSiteInformation;

    $validatedData = [
        'name' => 'New Name',
        'slug' => 'new-slug',
        'description' => 'New description',
        'settings' => [
            'product_prefix' => 'NEW',
        ],
    ];

    $updatedSite = $action->handle($site, $validatedData);

    expect($updatedSite->name)->toBe('New Name')
        ->and($updatedSite->slug)->toBe('new-slug')
        ->and($updatedSite->description)->toBe('New description')
        ->and($updatedSite->settings['product_prefix'])->toBe('NEW');
});

test('it preserves existing settings when updating', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create([
        'user_id' => $user->id,
        'settings' => [
            'product_prefix' => 'OLD',
            'other_setting' => 'preserved',
        ],
    ]);

    $action = new UpdateSiteInformation;

    $validatedData = [
        'name' => 'Updated Name',
        'slug' => 'updated-slug',
        'description' => null,
        'settings' => [
            'product_prefix' => 'NEW',
        ],
    ];

    $updatedSite = $action->handle($site, $validatedData);

    expect($updatedSite->settings['product_prefix'])->toBe('NEW')
        ->and($updatedSite->settings['other_setting'])->toBe('preserved');
});

test('it handles null description', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['user_id' => $user->id]);

    $action = new UpdateSiteInformation;

    $validatedData = [
        'name' => 'Test Site',
        'slug' => 'test-site',
        'description' => null,
    ];

    $updatedSite = $action->handle($site, $validatedData);

    expect($updatedSite->description)->toBeNull();
});
