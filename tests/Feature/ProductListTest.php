<?php

use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\Site;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    $this->seed(\Database\Seeders\ProductPermissionSeeder::class);

    $this->site = Site::factory()->create();
    $this->user = User::factory()->create(['site_id' => $this->site->id]);
    $this->user->assignRole('SiteAdmin');

    $this->otherSite = Site::factory()->create();
    $this->otherUser = User::factory()->create(['site_id' => $this->otherSite->id]);
    $this->otherUser->assignRole('SiteAdmin');
});

function createProductForSiteForList(Site $site, array $overrides = []): Product {
    $productType = ProductType::factory()->create(['site_id' => $site->id]);
    $supplier = Supplier::factory()->forSite($site)->create();
    $category = Category::factory()->forSite($site)->create();
    $unit = Unit::factory()->create();
    $warehouse = Warehouse::factory()->forSite($site)->create();
    $location = Location::factory()->forWarehouse($warehouse)->create();

    return Product::factory()->create(array_merge([
        'site_id' => $site->id,
        'product_type_id' => $productType->id,
        'supplier_id' => $supplier->id,
        'category_id' => $category->id,
        'unit_id' => $unit->id,
        'default_location_id' => $location->id,
    ], $overrides));
}

test('site admin can view products index scoped to their site', function () {
    createProductForSiteForList($this->site);
    createProductForSiteForList($this->site);
    createProductForSiteForList($this->site);

    createProductForSiteForList($this->otherSite);
    createProductForSiteForList($this->otherSite);

    $response = $this->actingAs($this->user)
        ->get(route('products.index', ['site' => $this->site->slug]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Products/Index')
            ->has('products.data', 3)
        );
});

test('products index supports searching by name and code', function () {
    createProductForSiteForList($this->site, [
        'name' => 'Apple iPhone',
        'code' => 'IP-001',
    ]);

    createProductForSiteForList($this->site, [
        'name' => 'Samsung Galaxy',
        'code' => 'SG-002',
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('products.index', ['site' => $this->site->slug, 'search' => 'iPhone']));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Products/Index')
            ->has('products.data', 1)
            ->where('products.data.0.code', 'IP-001')
        );

    $response = $this->actingAs($this->user)
        ->get(route('products.index', ['site' => $this->site->slug, 'search' => 'SG-002']));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Products/Index')
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Samsung Galaxy')
        );
});

test('user without products permissions cannot access products index', function () {
    $userWithoutPermission = User::factory()->create(['site_id' => $this->site->id]);

    $response = $this->actingAs($userWithoutPermission)
        ->get(route('products.index', ['site' => $this->site->slug]));

    $response->assertForbidden();
});

