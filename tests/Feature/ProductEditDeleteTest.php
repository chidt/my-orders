<?php

use App\Models\Category;
use App\Models\Attribute;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\Site;
use App\Models\Supplier;
use App\Models\Tag;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\UploadedFile;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    $this->seed(\Database\Seeders\ProductPermissionSeeder::class);

    $this->site = Site::factory()->create();
    $this->user = User::factory()->create(['site_id' => $this->site->id]);
    $this->user->assignRole('SiteAdmin');

    $this->otherSite = Site::factory()->create();
});

function createProductForSiteForEditDelete(Site $site, array $overrides = []): Product
{
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

test('site admin can view product edit page', function () {
    $product = createProductForSiteForEditDelete($this->site, ['name' => 'My Product', 'code' => 'PRD-001']);

    $response = $this->actingAs($this->user)
        ->get(route('products.edit', ['site' => $this->site->slug, 'product' => $product->id]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Products/Edit')
            ->has('product')
            ->where('product.id', $product->id)
            ->where('product.name', 'My Product')
        );
});

test('site admin can update product', function () {
    $product = createProductForSiteForEditDelete($this->site);
    $newCategory = Category::factory()->forSite($this->site)->create(['name' => 'New Cat']);

    $payload = [
        'name' => 'Updated Product Name',
        'code' => $product->code,
        'supplier_code' => 'SUP-99',
        'description' => 'Updated desc',
        'category_id' => $newCategory->id,
        'supplier_id' => $product->supplier_id,
        'unit_id' => $product->unit_id,
        'product_type_id' => $product->product_type_id,
        'default_location_id' => $product->default_location_id,
        'qty_in_stock' => 5,
        'weight' => 2.5,
        'price' => 150000,
        'partner_price' => 120000,
        'purchase_price' => 80000,
        'order_closing_date' => null,
        'tags' => [],
    ];

    $response = $this->actingAs($this->user)
        ->put(route('products.update', ['site' => $this->site->slug, 'product' => $product->id]), $payload);

    $response->assertRedirect(route('products.index', ['site' => $this->site->slug]))
        ->assertSessionHas('success');

    $product->refresh();
    expect($product->name)->toBe('Updated Product Name');
    expect($product->supplier_code)->toBe('SUP-99');
    expect($product->category_id)->toBe($newCategory->id);
    expect((float) $product->price)->toBe(150000.0);
});

test('site admin can delete product', function () {
    $product = createProductForSiteForEditDelete($this->site);
    $productId = $product->id;

    $response = $this->actingAs($this->user)
        ->delete(route('products.destroy', ['site' => $this->site->slug, 'product' => $product->id]));

    $response->assertRedirect(route('products.index', ['site' => $this->site->slug]))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('products', ['id' => $productId]);
});

test('site admin cannot edit other site product', function () {
    $otherProduct = createProductForSiteForEditDelete($this->otherSite);

    $response = $this->actingAs($this->user)
        ->get(route('products.edit', ['site' => $this->site->slug, 'product' => $otherProduct->id]));

    $response->assertNotFound();
});

test('site admin cannot delete other site product', function () {
    $otherProduct = createProductForSiteForEditDelete($this->otherSite);

    $response = $this->actingAs($this->user)
        ->delete(route('products.destroy', ['site' => $this->site->slug, 'product' => $otherProduct->id]));

    $response->assertNotFound();
    $this->assertDatabaseHas('products', ['id' => $otherProduct->id]);
});

test('site admin can update product with multiple attributes, tags and images', function () {
    $product = createProductForSiteForEditDelete($this->site, [
        'code' => 'PRD-EDIT-001',
    ]);

    $tagA = Tag::factory()->forSite($this->site)->create(['name' => 'Hot']);
    $tagB = Tag::factory()->forSite($this->site)->create(['name' => 'Bestseller']);

    $size = Attribute::factory()->forSite($this->site)->create(['name' => 'Size', 'code' => 'size', 'order' => 1]);
    $color = Attribute::factory()->forSite($this->site)->create(['name' => 'Color', 'code' => 'color', 'order' => 2]);

    $payload = [
        'name' => 'Updated With Variants',
        'code' => 'PRD-EDIT-001',
        'supplier_code' => 'SUP-EDIT',
        'description' => 'Updated with tags and images',
        'category_id' => $product->category_id,
        'supplier_id' => $product->supplier_id,
        'unit_id' => $product->unit_id,
        'product_type_id' => $product->product_type_id,
        'default_location_id' => $product->default_location_id,
        'qty_in_stock' => 7,
        'weight' => 3.1,
        'price' => 200000,
        'partner_price' => 180000,
        'purchase_price' => 120000,
        'order_closing_date' => null,
        'main_image' => UploadedFile::fake()->image('updated-main.jpg', 1200, 1200),
        'slide_images' => [
            UploadedFile::fake()->image('updated-slide-1.jpg', 1200, 1200),
            UploadedFile::fake()->image('updated-slide-2.jpg', 1200, 1200),
        ],
        'tags' => [$tagA->id, $tagB->id],
        'attributes' => [
            [
                'attribute_id' => $size->id,
                'values' => [
                    ['code' => 'S', 'value' => 'Small', 'order' => 1, 'addition_value' => 0],
                    ['code' => 'M', 'value' => 'Medium', 'order' => 2, 'addition_value' => 0],
                ],
            ],
            [
                'attribute_id' => $color->id,
                'values' => [
                    ['code' => 'RED', 'value' => 'Red', 'order' => 1, 'addition_value' => 0],
                    ['code' => 'BLUE', 'value' => 'Blue', 'order' => 2, 'addition_value' => 0],
                ],
            ],
        ],
    ];

    $response = $this->actingAs($this->user)
        ->put(route('products.update', ['site' => $this->site->slug, 'product' => $product->id]), $payload);

    $response->assertRedirect(route('products.index', ['site' => $this->site->slug]))
        ->assertSessionHas('success');

    $product->refresh();
    $product->load('tags', 'productItems');

    expect($product->name)->toBe('Updated With Variants');
    expect($product->tags->pluck('id')->sort()->values()->all())
        ->toBe([$tagA->id, $tagB->id]);
    expect($product->getMedia('main_image')->count())->toBe(1);
    expect($product->getMedia('product_slider_images')->count())->toBe(2);
    expect($product->productItems->count())->toBe(4);
    expect($product->productItems->pluck('sku')->all())->toContain('PRD-EDIT-001-S-RED');
    expect($product->productItems->pluck('sku')->all())->toContain('PRD-EDIT-001-M-BLUE');
});

test('site admin can keep existing uploaded variant image after saving edit again', function () {
    $product = createProductForSiteForEditDelete($this->site, [
        'code' => 'PRD-EDIT-UPLOAD-001',
    ]);

    $size = Attribute::factory()->forSite($this->site)->create(['name' => 'Size', 'code' => 'size', 'order' => 1]);
    $color = Attribute::factory()->forSite($this->site)->create(['name' => 'Color', 'code' => 'color', 'order' => 2]);

    $basePayload = [
        'name' => 'Upload Keep Product',
        'code' => 'PRD-EDIT-UPLOAD-001',
        'supplier_code' => 'SUP-KEEP',
        'description' => 'Test keep existing variant upload',
        'category_id' => $product->category_id,
        'supplier_id' => $product->supplier_id,
        'unit_id' => $product->unit_id,
        'product_type_id' => $product->product_type_id,
        'default_location_id' => $product->default_location_id,
        'qty_in_stock' => 5,
        'weight' => 1.5,
        'price' => 120000,
        'partner_price' => 110000,
        'purchase_price' => 90000,
        'order_closing_date' => null,
        'tags' => [],
        'attributes' => [
            [
                'attribute_id' => $size->id,
                'values' => [
                    ['code' => 'S', 'value' => 'Small', 'order' => 1, 'addition_value' => 0],
                ],
            ],
            [
                'attribute_id' => $color->id,
                'values' => [
                    ['code' => 'RED', 'value' => 'Red', 'order' => 1, 'addition_value' => 0],
                    ['code' => 'BLUE', 'value' => 'Blue', 'order' => 2, 'addition_value' => 0],
                ],
            ],
        ],
    ];

    $firstPayload = $basePayload;
    $firstPayload['variant_image_file_keys'] = ['S-BLUE'];
    $firstPayload['variant_image_files'] = [UploadedFile::fake()->image('keep-variant-upload.jpg', 1000, 1000)];

    $firstResponse = $this->actingAs($this->user)
        ->put(route('products.update', ['site' => $this->site->slug, 'product' => $product->id]), $firstPayload);

    $firstResponse->assertRedirect(route('products.index', ['site' => $this->site->slug]))
        ->assertSessionHas('success');

    $product->refresh();
    $firstVariant = $product->productItems()->where('sku', 'PRD-EDIT-UPLOAD-001-S-BLUE')->firstOrFail();
    $existingMediaId = (int) $firstVariant->media_id;

    expect($existingMediaId)->toBeGreaterThan(0);
    expect($firstVariant->getMedia('variant_images')->count())->toBe(1);

    $secondPayload = $basePayload;
    $secondPayload['variant_images'] = [
        [
            'key' => 'S-BLUE',
            'media_id' => $existingMediaId,
            'slide_index' => null,
            'use_main_image' => false,
        ],
    ];

    $secondResponse = $this->actingAs($this->user)
        ->put(route('products.update', ['site' => $this->site->slug, 'product' => $product->id]), $secondPayload);

    $secondResponse->assertRedirect(route('products.index', ['site' => $this->site->slug]))
        ->assertSessionHas('success');

    $product->refresh();
    $secondVariant = $product->productItems()->where('sku', 'PRD-EDIT-UPLOAD-001-S-BLUE')->firstOrFail();

    expect((int) $secondVariant->media_id)->toBe($existingMediaId);
    expect($secondVariant->getMedia('variant_images')->count())->toBe(1);
    expect((bool) $secondVariant->is_parent_image)->toBeFalse();
    expect((bool) $secondVariant->is_parent_slider_image)->toBeFalse();
});
