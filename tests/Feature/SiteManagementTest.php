<?php

use App\Models\Site;
use App\Models\User;
use Spatie\Permission\Models\Permission;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
});

test('authorized user can view site edit form', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['user_id' => $user->id]);

    $user->assignRole('SiteAdmin'); // Assign admin role for dashboard access

    $response = $this->actingAs($user)->get(route('site.edit'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($assert) => $assert->component('settings/Site')
        ->has('site')
        ->where('site.id', $site->id)
        ->where('site.name', $site->name)
    );
});

test('user without permission cannot access site edit form', function () {
    $user = User::factory()->create();
    Site::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get(route('site.edit'));

    $response->assertStatus(403);
});

test('authorized user can update site information', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['user_id' => $user->id]);

    $user->assignRole('SiteAdmin'); // Assign admin role for dashboard access

    $updateData = [
        'name' => 'Updated Site Name',
        'slug' => 'updated-site-slug',
        'description' => 'Updated description',
        'settings' => [
            'product_prefix' => 'UPD',
        ],
    ];

    $response = $this->actingAs($user)->put(route('site.update'), $updateData);

    $response->assertRedirect();
    $response->assertSessionHas('status', 'Thông tin trang web đã được cập nhật thành công! Slug đã được thay đổi.');

    $site->refresh();
    expect($site->name)->toBe('Updated Site Name')
        ->and($site->slug)->toBe('updated-site-slug')
        ->and($site->description)->toBe('Updated description')
        ->and($site->settings['product_prefix'])->toBe('UPD');
});

test('validation works for required fields', function () {
    $user = User::factory()->create();
    Site::factory()->create(['user_id' => $user->id]);
    $user->assignRole('SiteAdmin'); // Assign admin role for dashboard access

    $response = $this->actingAs($user)->put(route('site.update'), [
        'name' => '', // Required field empty
        'slug' => '',
    ]);

    $response->assertSessionHasErrors(['name', 'slug']);
});

test('validation works for slug format', function () {
    $user = User::factory()->create();
    Site::factory()->create(['user_id' => $user->id]);

    $user->assignRole('SiteAdmin'); // Assign admin role for dashboard access

    $response = $this->actingAs($user)->put(route('site.update'), [
        'name' => 'Test Site',
        'slug' => 'Invalid Slug With Spaces!',
    ]);

    $response->assertSessionHasErrors(['slug']);
});

test('validation works for product prefix format', function () {
    $user = User::factory()->create();
    Site::factory()->create(['user_id' => $user->id]);

    $user->assignRole('SiteAdmin'); // Assign admin role for dashboard access

    $response = $this->actingAs($user)->put(route('site.update'), [
        'name' => 'Test Site',
        'slug' => 'test-site',
        'settings' => [
            'product_prefix' => 'invalid_lowercase',
        ],
    ]);

    $response->assertSessionHasErrors(['settings.product_prefix']);
});

test('user without site gets 404', function () {
    $user = User::factory()->create();
    $user->assignRole('SiteAdmin'); // Assign admin role for dashboard access

    $response = $this->actingAs($user)->get(route('site.edit'));

    $response->assertStatus(403);
});

test('user cannot update if they dont own the site', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    Site::factory()->create(['user_id' => $otherUser->id]);

    $user->assignRole('SiteAdmin'); // Assign admin role for dashboard access

    $response = $this->actingAs($user)->get(route('site.edit'));

    $response->assertStatus(403);
});

test('it generates unique slugs when conflicts exist', function () {
    // Create sites with conflicting slugs
    Site::factory()->create(['slug' => 'test-site']);
    Site::factory()->create(['slug' => 'test-site-1']);

    $action = new \App\Actions\Site\GenerateSlugFromName;

    // Should generate test-site-2 when test-site and test-site-1 exist
    $uniqueSlug = $action->handleUnique('Test Site');
    expect($uniqueSlug)->toBe('test-site-2');
});
