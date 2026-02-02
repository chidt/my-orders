<?php

use App\Http\Requests\StoreSiteRequest;
use App\Models\Site;

test('site store request requires unique slug', function () {
    $existingSite = Site::factory()->create(['slug' => 'existing-slug']);

    $request = new StoreSiteRequest;
    $request->merge([
        'name' => 'Test Site',
        'slug' => 'existing-slug',
        'description' => 'A test site',
    ]);

    expect(fn () => $request->validate($request->rules()))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});

test('site store request accepts valid data', function () {
    $request = new StoreSiteRequest;
    $request->merge([
        'name' => 'Test Site',
        'slug' => 'unique-slug',
        'description' => 'A test site',
    ]);

    expect($request->validate($request->rules()))->toBeArray();
});

test('site model enforces unique slug in database', function () {
    Site::factory()->create(['slug' => 'existing-slug']);

    expect(fn () => Site::factory()->create(['slug' => 'existing-slug']))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

test('site can be created with unique slug', function () {
    $site = Site::factory()->create(['slug' => 'unique-slug']);

    expect($site)->toBeInstanceOf(Site::class);
    expect($site->slug)->toBe('unique-slug');
});

test('slug validation only allows lowercase letters numbers and hyphens', function () {
    $request = new StoreSiteRequest;
    $request->merge([
        'name' => 'Test Site',
        'slug' => 'Invalid_Slug!', // Contains underscore and exclamation
        'description' => 'A test site',
    ]);

    expect(fn () => $request->validate($request->rules()))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});
