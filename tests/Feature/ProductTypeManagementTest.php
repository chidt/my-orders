<?php

use App\Models\ProductType;
use App\Models\Site;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    $this->seed(\Database\Seeders\ProductTypePermissionSeeder::class);

    // Create a site and user
    $this->site = Site::factory()->create();
    $this->user = User::factory()->create(['site_id' => $this->site->id]);
    $this->user->assignRole('SiteAdmin');

    // Create another site and user for isolation testing
    $this->otherSite = Site::factory()->create();
    $this->otherUser = User::factory()->create(['site_id' => $this->otherSite->id]);
    $this->otherUser->assignRole('SiteAdmin');
});

test('site admin can view product types index', function () {
    ProductType::factory()->count(3)->create(['site_id' => $this->site->id]);
    ProductType::factory()->count(2)->create(['site_id' => $this->otherSite->id]);

    $response = $this->actingAs($this->user)
        ->get(route('product-types.index', ['site' => $this->site->slug]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Products/ProductTypes/Index')
            ->has('productTypes.data', 3) // Only sees own site's product types
        );
});

test('site admin can create product type', function () {
    $productTypeData = [
        'name' => 'Electronics',
        'order' => 10,
        'show_on_front' => true,
        'color' => '#ff0000',
    ];

    $response = $this->actingAs($this->user)
        ->post(route('product-types.store', ['site' => $this->site->slug]), $productTypeData);

    $response->assertRedirect(route('product-types.index', ['site' => $this->site->slug]))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('product_types', [
        'name' => 'Electronics',
        'site_id' => $this->site->id,
        'order' => 10,
        'show_on_front' => true,
        'color' => '#ff0000',
    ]);
});

test('product type name must be unique within site', function () {
    ProductType::factory()->create([
        'name' => 'Electronics',
        'site_id' => $this->site->id,
    ]);

    $productTypeData = [
        'name' => 'Electronics',
        'order' => 10,
        'show_on_front' => true,
        'color' => '#ff0000',
    ];

    $response = $this->actingAs($this->user)
        ->post(route('product-types.store', ['site' => $this->site->slug]), $productTypeData);

    $response->assertSessionHasErrors('name');
});

test('product type name can be duplicated across different sites', function () {
    ProductType::factory()->create([
        'name' => 'Electronics',
        'site_id' => $this->site->id,
    ]);

    $productTypeData = [
        'name' => 'Electronics',
        'order' => 10,
        'show_on_front' => true,
        'color' => '#ff0000',
    ];

    $response = $this->actingAs($this->otherUser)
        ->post(route('product-types.store', ['site' => $this->otherSite->slug]), $productTypeData);

    $response->assertRedirect(route('product-types.index', ['site' => $this->otherSite->slug]))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('product_types', [
        'name' => 'Electronics',
        'site_id' => $this->otherSite->id,
    ]);
});

test('site admin can update product type', function () {
    $productType = ProductType::factory()->create(['site_id' => $this->site->id]);

    $updateData = [
        'name' => 'Updated Name',
        'order' => 20,
        'show_on_front' => false,
        'color' => '#00ff00',
    ];

    $response = $this->actingAs($this->user)
        ->put(route('product-types.update', ['site' => $this->site->slug, 'product_type' => $productType]), $updateData);

    $response->assertRedirect(route('product-types.index', ['site' => $this->site->slug]))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('product_types', [
        'id' => $productType->id,
        'name' => 'Updated Name',
        'order' => 20,
        'show_on_front' => false,
        'color' => '#00ff00',
    ]);
});

test('site admin cannot access other site product types', function () {
    $otherProductType = ProductType::factory()->create(['site_id' => $this->otherSite->id]);

    $response = $this->actingAs($this->user)
        ->get(route('product-types.edit', ['site' => $this->site->slug, 'product_type' => $otherProductType]));

    $response->assertNotFound(); // 404 is expected since model is scoped to user's site
});

test('site admin can delete product type when no products are using it', function () {
    $productType = ProductType::factory()->create(['site_id' => $this->site->id]);

    $response = $this->actingAs($this->user)
        ->delete(route('product-types.destroy', ['site' => $this->site->slug, 'product_type' => $productType]));

    $response->assertRedirect(route('product-types.index', ['site' => $this->site->slug]))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('product_types', ['id' => $productType->id]);
});

test('validates color format', function () {
    $productTypeData = [
        'name' => 'Electronics',
        'color' => 'invalid-color',
    ];

    $response = $this->actingAs($this->user)
        ->post(route('product-types.store', ['site' => $this->site->slug]), $productTypeData);

    $response->assertSessionHasErrors('color');
});

test('validates order is non-negative integer', function () {
    $productTypeData = [
        'name' => 'Electronics',
        'order' => -5,
    ];

    $response = $this->actingAs($this->user)
        ->post(route('product-types.store', ['site' => $this->site->slug]), $productTypeData);

    $response->assertSessionHasErrors('order');
});

test('user without permissions cannot access product types', function () {
    $userWithoutPermission = User::factory()->create(['site_id' => $this->site->id]);

    $response = $this->actingAs($userWithoutPermission)
        ->get(route('product-types.index', ['site' => $this->site->slug]));

    $response->assertForbidden();
});
