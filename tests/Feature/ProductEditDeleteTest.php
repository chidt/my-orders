<?php

use App\Models\Attribute;
use App\Models\Category;
use App\Models\Location;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\ProductAttributeValue;
use App\Models\ProductItem;
use App\Models\ProductItemAttributeValue;
use App\Models\ProductType;
use App\Models\Site;
use App\Models\Supplier;
use App\Models\Tag;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
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

    $response->assertRedirect(route('products.edit', ['site' => $this->site->slug, 'product' => $product->id]))
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

    $response->assertRedirect(route('products.edit', ['site' => $this->site->slug, 'product' => $product->id]))
        ->assertSessionHas('success');

    $product->refresh();
    $product->load('tags', 'productItems');

    expect($product->name)->toBe('Updated With Variants');
    expect($product->tags->pluck('id')->sort()->values()->all())
        ->toBe([$tagA->id, $tagB->id]);
    expect($product->getMedia('main_image')->count())->toBe(1);
    expect($product->getMedia('product_slider_images')->count())->toBe(2);
    expect($product->productItems->count())->toBe(0);
});

test('site admin can update product without regenerating product items from variant payload', function () {
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

    $firstResponse->assertRedirect(route('products.edit', ['site' => $this->site->slug, 'product' => $product->id]))
        ->assertSessionHas('success');

    $product->refresh();
    expect($product->productItems()->count())->toBe(0);

    $secondPayload = $basePayload;
    $secondPayload['variant_images'] = [
        [
            'key' => 'S-BLUE',
            'media_id' => 999999,
            'slide_index' => null,
            'use_main_image' => false,
        ],
    ];

    $secondResponse = $this->actingAs($this->user)
        ->put(route('products.update', ['site' => $this->site->slug, 'product' => $product->id]), $secondPayload);

    $secondResponse->assertRedirect(route('products.edit', ['site' => $this->site->slug, 'product' => $product->id]))
        ->assertSessionHas('success');

    $product->refresh();
    expect($product->productItems()->count())->toBe(0);
});

test('site admin keeps existing product item ids when saving unchanged variants', function () {
    $product = createProductForSiteForEditDelete($this->site, [
        'code' => 'PRD-KEEP-IDS-001',
    ]);

    $size = Attribute::factory()->forSite($this->site)->create(['name' => 'Size', 'code' => 'size', 'order' => 1]);
    $color = Attribute::factory()->forSite($this->site)->create(['name' => 'Color', 'code' => 'color', 'order' => 2]);

    $payload = [
        'name' => 'Keep Item IDs Product',
        'code' => 'PRD-KEEP-IDS-001',
        'supplier_code' => 'SUP-KEEP-ID',
        'description' => 'Ensure product_items ids are stable for unchanged update payload',
        'category_id' => $product->category_id,
        'supplier_id' => $product->supplier_id,
        'unit_id' => $product->unit_id,
        'product_type_id' => $product->product_type_id,
        'default_location_id' => $product->default_location_id,
        'qty_in_stock' => 9,
        'weight' => 2.1,
        'price' => 210000,
        'partner_price' => 190000,
        'purchase_price' => 130000,
        'order_closing_date' => null,
        'tags' => [],
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

    $itemA = ProductItem::factory()->forProduct($product)->create([
        'sku' => 'PRD-KEEP-IDS-001-LEGACY-A',
    ]);
    $itemB = ProductItem::factory()->forProduct($product)->create([
        'sku' => 'PRD-KEEP-IDS-001-LEGACY-B',
    ]);

    $idsBefore = $product->productItems()
        ->pluck('id', 'sku')
        ->map(fn ($id) => (int) $id)
        ->all();

    expect($idsBefore)->toBe([
        'PRD-KEEP-IDS-001-LEGACY-A' => (int) $itemA->id,
        'PRD-KEEP-IDS-001-LEGACY-B' => (int) $itemB->id,
    ]);

    $secondResponse = $this->actingAs($this->user)
        ->put(route('products.update', ['site' => $this->site->slug, 'product' => $product->id]), $payload);

    $secondResponse->assertRedirect(route('products.edit', ['site' => $this->site->slug, 'product' => $product->id]))
        ->assertSessionHas('success');

    $product->refresh();
    $idsAfter = $product->productItems()
        ->pluck('id', 'sku')
        ->map(fn ($id) => (int) $id)
        ->all();

    expect($idsAfter)->toBe($idsBefore);
});

test('site admin updates existing product attribute values by id when code changes', function () {
    $product = createProductForSiteForEditDelete($this->site, [
        'code' => 'PRD-KEEP-PAV-001',
    ]);

    $size = Attribute::factory()->forSite($this->site)->create(['name' => 'Size', 'code' => 'size', 'order' => 1]);

    $small = ProductAttributeValue::create([
        'code' => 'S',
        'value' => 'Small',
        'order' => 1,
        'addition_value' => 0,
        'partner_addition_value' => 0,
        'purchase_addition_value' => 0,
        'product_id' => $product->id,
        'attribute_id' => $size->id,
    ]);
    $medium = ProductAttributeValue::create([
        'code' => 'M',
        'value' => 'Medium',
        'order' => 2,
        'addition_value' => 0,
        'partner_addition_value' => 0,
        'purchase_addition_value' => 0,
        'product_id' => $product->id,
        'attribute_id' => $size->id,
    ]);

    $payload = [
        'name' => 'Keep Product Attribute Value IDs',
        'code' => 'PRD-KEEP-PAV-001',
        'supplier_code' => 'SUP-PAV-ID',
        'description' => 'Update attribute values but keep ids',
        'category_id' => $product->category_id,
        'supplier_id' => $product->supplier_id,
        'unit_id' => $product->unit_id,
        'product_type_id' => $product->product_type_id,
        'default_location_id' => $product->default_location_id,
        'qty_in_stock' => 5,
        'weight' => 1.2,
        'price' => 120000,
        'partner_price' => 110000,
        'purchase_price' => 90000,
        'order_closing_date' => null,
        'tags' => [],
        'attributes' => [
            [
                'attribute_id' => $size->id,
                'values' => [
                    [
                        'id' => $small->id,
                        'code' => 'SMALL',
                        'value' => 'Small Updated',
                        'order' => 1,
                        'addition_value' => 1000,
                    ],
                    [
                        'id' => $medium->id,
                        'code' => 'MEDIUM',
                        'value' => 'Medium Updated',
                        'order' => 2,
                        'addition_value' => 2000,
                    ],
                ],
            ],
        ],
    ];

    $response = $this->actingAs($this->user)
        ->put(route('products.update', ['site' => $this->site->slug, 'product' => $product->id]), $payload);

    $response->assertRedirect(route('products.edit', ['site' => $this->site->slug, 'product' => $product->id]))
        ->assertSessionHas('success');

    $small->refresh();
    $medium->refresh();

    expect($small->code)->toBe('SMALL');
    expect($small->value)->toBe('Small Updated');
    expect((int) $small->id)->toBe((int) $payload['attributes'][0]['values'][0]['id']);

    expect($medium->code)->toBe('MEDIUM');
    expect($medium->value)->toBe('Medium Updated');
    expect((int) $medium->id)->toBe((int) $payload['attributes'][0]['values'][1]['id']);
});

test('site admin sees field errors when duplicate attribute value codes are submitted on update', function () {
    $product = createProductForSiteForEditDelete($this->site, [
        'code' => 'PRD-DUP-CODE-001',
    ]);

    $size = Attribute::factory()->forSite($this->site)->create(['name' => 'Size', 'code' => 'size', 'order' => 1]);

    $payload = [
        'name' => 'Duplicate Code Product',
        'code' => 'PRD-DUP-CODE-001',
        'supplier_code' => 'SUP-DUP',
        'description' => 'Duplicate code validation on update',
        'category_id' => $product->category_id,
        'supplier_id' => $product->supplier_id,
        'unit_id' => $product->unit_id,
        'product_type_id' => $product->product_type_id,
        'default_location_id' => $product->default_location_id,
        'qty_in_stock' => 5,
        'weight' => 1.2,
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
                    ['code' => 's', 'value' => 'Small Duplicate', 'order' => 2, 'addition_value' => 0],
                ],
            ],
        ],
    ];

    $response = $this->actingAs($this->user)
        ->from(route('products.edit', ['site' => $this->site->slug, 'product' => $product->id]))
        ->put(route('products.update', ['site' => $this->site->slug, 'product' => $product->id]), $payload);

    $response
        ->assertRedirect(route('products.edit', ['site' => $this->site->slug, 'product' => $product->id]))
        ->assertSessionHasErrors([
            'attributes.0.values.0.code',
            'attributes.0.values.1.code',
        ]);
});

test('site admin can sync child products without deleting existing product items', function () {
    $product = createProductForSiteForEditDelete($this->site, [
        'code' => 'PRD-SYNC-CHILD-001',
        'price' => 100000,
        'partner_price' => 90000,
        'purchase_price' => 70000,
    ]);

    $size = Attribute::factory()->forSite($this->site)->create(['name' => 'Size', 'code' => 'size', 'order' => 1]);
    $color = Attribute::factory()->forSite($this->site)->create(['name' => 'Color', 'code' => 'color', 'order' => 2]);
    $gender = Attribute::factory()->forSite($this->site)->create(['name' => 'Gender', 'code' => 'gender', 'order' => 3]);

    $sizeX = ProductAttributeValue::create([
        'code' => 'X',
        'value' => 'Size X',
        'order' => 1,
        'addition_value' => 0,
        'partner_addition_value' => 0,
        'purchase_addition_value' => 0,
        'product_id' => $product->id,
        'attribute_id' => $size->id,
    ]);
    $sizeL = ProductAttributeValue::create([
        'code' => 'L',
        'value' => 'Size L',
        'order' => 2,
        'addition_value' => 0,
        'partner_addition_value' => 0,
        'purchase_addition_value' => 0,
        'product_id' => $product->id,
        'attribute_id' => $size->id,
    ]);
    $colorBlue = ProductAttributeValue::create([
        'code' => 'BLUE',
        'value' => 'Blue',
        'order' => 1,
        'addition_value' => 0,
        'partner_addition_value' => 0,
        'purchase_addition_value' => 0,
        'product_id' => $product->id,
        'attribute_id' => $color->id,
    ]);
    $colorOrange = ProductAttributeValue::create([
        'code' => 'ORANGE',
        'value' => 'Orange',
        'order' => 2,
        'addition_value' => 0,
        'partner_addition_value' => 0,
        'purchase_addition_value' => 0,
        'product_id' => $product->id,
        'attribute_id' => $color->id,
    ]);

    $legacyXBlue = ProductItem::factory()->forProduct($product)->create(['sku' => 'LEGACY-X-BLUE']);
    $legacyXOrange = ProductItem::factory()->forProduct($product)->create(['sku' => 'LEGACY-X-ORANGE']);
    $legacyLBlue = ProductItem::factory()->forProduct($product)->create(['sku' => 'LEGACY-L-BLUE']);
    $legacyLOrange = ProductItem::factory()->forProduct($product)->create(['sku' => 'LEGACY-L-ORANGE']);

    ProductItemAttributeValue::create(['product_item_id' => $legacyXBlue->id, 'product_attribute_value_id' => $sizeX->id]);
    ProductItemAttributeValue::create(['product_item_id' => $legacyXBlue->id, 'product_attribute_value_id' => $colorBlue->id]);
    ProductItemAttributeValue::create(['product_item_id' => $legacyXOrange->id, 'product_attribute_value_id' => $sizeX->id]);
    ProductItemAttributeValue::create(['product_item_id' => $legacyXOrange->id, 'product_attribute_value_id' => $colorOrange->id]);
    ProductItemAttributeValue::create(['product_item_id' => $legacyLBlue->id, 'product_attribute_value_id' => $sizeL->id]);
    ProductItemAttributeValue::create(['product_item_id' => $legacyLBlue->id, 'product_attribute_value_id' => $colorBlue->id]);
    ProductItemAttributeValue::create(['product_item_id' => $legacyLOrange->id, 'product_attribute_value_id' => $sizeL->id]);
    ProductItemAttributeValue::create(['product_item_id' => $legacyLOrange->id, 'product_attribute_value_id' => $colorOrange->id]);

    $payload = [
        'name' => 'Sync Child Product',
        'code' => 'PRD-SYNC-CHILD-001',
        'supplier_code' => 'SUP-SYNC',
        'description' => 'Sync child products after editing attributes',
        'category_id' => $product->category_id,
        'supplier_id' => $product->supplier_id,
        'unit_id' => $product->unit_id,
        'product_type_id' => $product->product_type_id,
        'default_location_id' => $product->default_location_id,
        'qty_in_stock' => 5,
        'weight' => 1.2,
        'price' => 100000,
        'partner_price' => 90000,
        'purchase_price' => 70000,
        'order_closing_date' => null,
        'tags' => [],
        'attributes' => [
            [
                'attribute_id' => $size->id,
                'values' => [
                    [
                        'id' => $sizeX->id,
                        'code' => 'X',
                        'value' => 'Size X',
                        'order' => 1,
                        'addition_value' => 0,
                        'partner_addition_value' => 0,
                        'purchase_addition_value' => 0,
                    ],
                    [
                        'id' => $sizeL->id,
                        'code' => 'L',
                        'value' => 'Size L',
                        'order' => 2,
                        'addition_value' => 100,
                        'partner_addition_value' => 100,
                        'purchase_addition_value' => 100,
                    ],
                ],
            ],
            [
                'attribute_id' => $color->id,
                'values' => [
                    [
                        'id' => $colorBlue->id,
                        'code' => 'BLUE',
                        'value' => 'Blue',
                        'order' => 1,
                        'addition_value' => 0,
                        'partner_addition_value' => 0,
                        'purchase_addition_value' => 0,
                    ],
                    [
                        'id' => $colorOrange->id,
                        'code' => 'ORANGE',
                        'value' => 'Orange',
                        'order' => 2,
                        'addition_value' => 0,
                        'partner_addition_value' => 0,
                        'purchase_addition_value' => 0,
                    ],
                    [
                        'code' => 'RED',
                        'value' => 'Red',
                        'order' => 3,
                        'addition_value' => 0,
                        'partner_addition_value' => 0,
                        'purchase_addition_value' => 0,
                    ],
                ],
            ],
            [
                'attribute_id' => $gender->id,
                'values' => [
                    [
                        'code' => 'F',
                        'value' => 'Female',
                        'order' => 1,
                        'addition_value' => 0,
                        'partner_addition_value' => 0,
                        'purchase_addition_value' => 0,
                    ],
                ],
            ],
        ],
    ];

    $response = $this->actingAs($this->user)
        ->put(route('products.update', ['site' => $this->site->slug, 'product' => $product->id]), $payload);

    $response->assertRedirect(route('products.edit', ['site' => $this->site->slug, 'product' => $product->id]))
        ->assertSessionHas('success');

    $syncResponse = $this->actingAs($this->user)
        ->from(route('products.edit', ['site' => $this->site->slug, 'product' => $product->id]))
        ->post(route('products.sync-child-products', [
            'site' => $this->site->slug,
            'product' => $product->id,
        ]));

    $syncResponse->assertRedirect(route('products.edit', ['site' => $this->site->slug, 'product' => $product->id]))
        ->assertSessionHas('success');

    $legacyItemIdsBefore = [
        (int) $legacyXBlue->id,
        (int) $legacyXOrange->id,
        (int) $legacyLBlue->id,
        (int) $legacyLOrange->id,
    ];

    $legacyItemsAfter = ProductItem::query()
        ->whereIn('id', $legacyItemIdsBefore)
        ->orderBy('id')
        ->get();

    expect($legacyItemsAfter->pluck('id')->map(fn ($id) => (int) $id)->all())
        ->toBe($legacyItemIdsBefore);

    expect((float) $legacyLBlue->fresh()->price)->toBe(100100.0);
    expect((float) $legacyLOrange->fresh()->price)->toBe(100100.0);

    $product->refresh();
    expect($product->productItems()->count())->toBe(10);
    expect(ProductItem::query()->where('product_id', $product->id)->where('sku', 'PRD-SYNC-CHILD-001-L-RED-F')->exists())->toBeTrue();
});

test('site admin can sync child products and save uploaded variant image', function () {
    $product = createProductForSiteForEditDelete($this->site, [
        'code' => 'PRD-SYNC-IMG-001',
        'price' => 100000,
        'partner_price' => 90000,
        'purchase_price' => 70000,
    ]);

    $size = Attribute::factory()->forSite($this->site)->create(['name' => 'Size', 'code' => 'size', 'order' => 1]);
    $color = Attribute::factory()->forSite($this->site)->create(['name' => 'Color', 'code' => 'color', 'order' => 2]);

    $sizeL = ProductAttributeValue::create([
        'code' => 'L',
        'value' => 'Size L',
        'order' => 1,
        'addition_value' => 0,
        'partner_addition_value' => 0,
        'purchase_addition_value' => 0,
        'product_id' => $product->id,
        'attribute_id' => $size->id,
    ]);
    $colorBlue = ProductAttributeValue::create([
        'code' => 'BLUE',
        'value' => 'Blue',
        'order' => 1,
        'addition_value' => 0,
        'partner_addition_value' => 0,
        'purchase_addition_value' => 0,
        'product_id' => $product->id,
        'attribute_id' => $color->id,
    ]);

    $existingItem = ProductItem::factory()->forProduct($product)->create([
        'sku' => 'PRD-SYNC-IMG-001-L-BLUE',
        'media_id' => null,
    ]);
    ProductItemAttributeValue::create([
        'product_item_id' => $existingItem->id,
        'product_attribute_value_id' => $sizeL->id,
    ]);
    ProductItemAttributeValue::create([
        'product_item_id' => $existingItem->id,
        'product_attribute_value_id' => $colorBlue->id,
    ]);

    $response = $this->actingAs($this->user)
        ->post(route('products.sync-child-products', [
            'site' => $this->site->slug,
            'product' => $product->id,
        ]), [
            'variant_image_file_keys' => ['item-'.$existingItem->id],
            'variant_image_files' => [
                UploadedFile::fake()->image('variant-upload.jpg', 1000, 1000),
            ],
        ]);

    $response->assertRedirect()
        ->assertSessionHas('success');

    $existingItem->refresh();
    expect($existingItem->media_id)->not->toBeNull();
    expect($existingItem->getMedia('variant_images')->count())->toBe(1);
});

test('site admin can delete child product from edit screen', function () {
    $product = createProductForSiteForEditDelete($this->site, [
        'code' => 'PRD-CHILD-DELETE-001',
    ]);

    $childProduct = ProductItem::factory()->forProduct($product)->create();

    $response = $this->actingAs($this->user)
        ->from(route('products.edit', ['site' => $this->site->slug, 'product' => $product->id]))
        ->delete(route('products.child-products.destroy', [
            'site' => $this->site->slug,
            'product' => $product->id,
            'productItem' => $childProduct->id,
        ]));

    $response->assertRedirect(route('products.edit', ['site' => $this->site->slug, 'product' => $product->id]))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('product_items', ['id' => $childProduct->id]);
});

test('site admin cannot delete child product that is used in order details', function () {
    $product = createProductForSiteForEditDelete($this->site, [
        'code' => 'PRD-CHILD-IN-USE-001',
    ]);
    $childProduct = ProductItem::factory()->forProduct($product)->create();
    $customer = \App\Models\Customer::factory()->forSite($this->site)->create();
    $order = Order::create([
        'payment_status' => 1,
        'order_number' => 'ORD-CHILD-IN-USE-001',
        'order_date' => Carbon::now(),
        'customer_type' => 1,
        'status' => 1,
        'shipping_payer' => 1,
        'phone' => '0900000000',
        'sale_channel' => 1,
        'customer_id' => $customer->id,
        'site_id' => $this->site->id,
    ]);

    OrderDetail::create([
        'payment_status' => 1,
        'status' => 1,
        'fulfillment_status' => 0,
        'qty' => 1,
        'price' => 100000,
        'discount' => 0,
        'addition_price' => 0,
        'total' => 100000,
        'product_item_id' => $childProduct->id,
        'order_id' => $order->id,
        'site_id' => $this->site->id,
    ]);

    $response = $this->actingAs($this->user)
        ->from(route('products.edit', ['site' => $this->site->slug, 'product' => $product->id]))
        ->delete(route('products.child-products.destroy', [
            'site' => $this->site->slug,
            'product' => $product->id,
            'productItem' => $childProduct->id,
        ]));

    $response->assertRedirect(route('products.edit', ['site' => $this->site->slug, 'product' => $product->id]))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('product_items', ['id' => $childProduct->id]);
});
