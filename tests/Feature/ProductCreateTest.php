<?php

use App\Models\Attribute;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductItem;
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

    $this->site = Site::factory()->create([
        'settings' => [
            'product_prefix' => 'PRD',
        ],
    ]);

    $this->user = User::factory()->create(['site_id' => $this->site->id]);
    $this->user->assignRole('SiteAdmin');
});

function basePayloadForSite(Site $site): array
{
    $productType = ProductType::factory()->create(['site_id' => $site->id, 'order' => 1]);
    $supplier = Supplier::factory()->forSite($site)->create();
    $category = Category::factory()->forSite($site)->create();
    $unit = Unit::factory()->piece()->create();
    $warehouse = Warehouse::factory()->forSite($site)->create();
    $location = Location::factory()->forWarehouse($warehouse)->create();

    return [
        'name' => 'Test Product',
        'code' => 'ABC',
        'supplier_code' => 'SUP-01',
        'description' => 'Desc',
        'category_id' => $category->id,
        'supplier_id' => $supplier->id,
        'unit_id' => $unit->id,
        'product_type_id' => $productType->id,
        'default_location_id' => $location->id,
        'qty_in_stock' => 10,
        'weight' => 1.2,
        'price' => 100000,
        'partner_price' => 90000,
        'purchase_price' => 70000,
        'order_closing_date' => now()->addDay()->toDateString(),
        'main_image' => UploadedFile::fake()->image('main.jpg', 1200, 1200),
        'tags' => [],
        'attributes' => [],
    ];
}

test('site admin can create simple product without attributes and gets 1 product item sku = code', function () {
    $payload = basePayloadForSite($this->site);
    $payload['attributes'] = null;

    $response = $this->actingAs($this->user)
        ->post(route('products.store', ['site' => $this->site->slug]), $payload);

    $response->assertRedirect(route('products.index', ['site' => $this->site->slug]))
        ->assertSessionHas('success');

    $product = Product::query()->where('code', 'ABC')->firstOrFail();
    expect($product->productItems()->count())->toBe(1);
    expect($product->productItems()->first()->sku)->toBe('ABC');
});

test('creating product with attributes generates combinations and sku order follows attribute.order', function () {
    $payload = basePayloadForSite($this->site);

    $size = Attribute::factory()->forSite($this->site)->create(['name' => 'Size', 'code' => 'size', 'order' => 1]);
    $color = Attribute::factory()->forSite($this->site)->create(['name' => 'Color', 'code' => 'color', 'order' => 2]);

    $payload['attributes'] = [
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
    ];

    $response = $this->actingAs($this->user)
        ->post(route('products.store', ['site' => $this->site->slug]), $payload);

    $response->assertRedirect(route('products.index', ['site' => $this->site->slug]))
        ->assertSessionHas('success');

    $product = Product::query()->where('code', 'ABC')->firstOrFail();
    expect($product->productItems()->count())->toBe(4);

    $skus = $product->productItems()->pluck('sku')->sort()->values()->all();
    expect($skus)->toContain('ABC-S-RED');
    expect($skus)->toContain('ABC-S-BLUE');
    expect($skus)->toContain('ABC-M-RED');
    expect($skus)->toContain('ABC-M-BLUE');
});

test('rejects creating product when combinations exceed 100', function () {
    $payload = basePayloadForSite($this->site);

    $a1 = Attribute::factory()->forSite($this->site)->create(['order' => 1]);
    $a2 = Attribute::factory()->forSite($this->site)->create(['order' => 2]);

    $values11 = collect(range(1, 11))->map(fn ($i) => [
        'code' => "V{$i}",
        'value' => "Value {$i}",
        'order' => $i,
        'addition_value' => 0,
    ])->all();

    $payload['attributes'] = [
        ['attribute_id' => $a1->id, 'values' => $values11],
        ['attribute_id' => $a2->id, 'values' => $values11],
    ];

    $response = $this->actingAs($this->user)
        ->post(route('products.store', ['site' => $this->site->slug]), $payload);

    $response->assertSessionHasErrors('attributes');
});

test('when sku already exists it auto adds suffix', function () {
    $existingPayload = basePayloadForSite($this->site);
    $existingProduct = Product::factory()->create([
        'site_id' => $this->site->id,
        'code' => 'EXISTING-001',
        'product_type_id' => $existingPayload['product_type_id'],
        'supplier_id' => $existingPayload['supplier_id'],
        'category_id' => $existingPayload['category_id'],
        'unit_id' => $existingPayload['unit_id'],
        'default_location_id' => $existingPayload['default_location_id'],
    ]);

    ProductItem::factory()->create([
        'sku' => 'ABC',
        'site_id' => $this->site->id,
        'product_id' => $existingProduct->id,
    ]);

    $payload = basePayloadForSite($this->site);
    $payload['attributes'] = null;

    $response = $this->actingAs($this->user)
        ->post(route('products.store', ['site' => $this->site->slug]), $payload);

    $response->assertRedirect(route('products.index', ['site' => $this->site->slug]));

    $product = Product::query()->where('code', 'ABC')->firstOrFail();
    expect($product->productItems()->first()->sku)->toBe('ABC-1');
});

test('creating product with many attributes, tags, and images works correctly', function () {
    $payload = basePayloadForSite($this->site);

    $size = Attribute::factory()->forSite($this->site)->create(['name' => 'Size', 'code' => 'size', 'order' => 1]);
    $color = Attribute::factory()->forSite($this->site)->create(['name' => 'Color', 'code' => 'color', 'order' => 2]);
    $material = Attribute::factory()->forSite($this->site)->create(['name' => 'Material', 'code' => 'material', 'order' => 3]);

    $tagA = Tag::factory()->forSite($this->site)->create(['name' => 'New']);
    $tagB = Tag::factory()->forSite($this->site)->create(['name' => 'Featured']);

    $payload['tags'] = [$tagA->id, $tagB->id];
    $payload['slide_images'] = [
        UploadedFile::fake()->image('slide-1.jpg', 1200, 1200),
        UploadedFile::fake()->image('slide-2.jpg', 1200, 1200),
    ];
    $payload['attributes'] = [
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
        [
            'attribute_id' => $material->id,
            'values' => [
                ['code' => 'COTTON', 'value' => 'Cotton', 'order' => 1, 'addition_value' => 0],
            ],
        ],
    ];
    $payload['variant_image_file_keys'] = ['M-BLUE-COTTON'];
    $payload['variant_image_files'] = [UploadedFile::fake()->image('variant-upload.jpg', 1000, 1000)];

    $response = $this->actingAs($this->user)
        ->post(route('products.store', ['site' => $this->site->slug]), $payload);

    $response->assertRedirect(route('products.index', ['site' => $this->site->slug]))
        ->assertSessionHas('success');

    $product = Product::query()->where('code', 'ABC')->firstOrFail();
    $product->refresh();
    $product->load('tags', 'productItems');

    expect($product->tags->pluck('id')->sort()->values()->all())
        ->toBe([$tagA->id, $tagB->id]);
    expect($product->getMedia('main_image')->count())->toBe(1);
    expect($product->getMedia('product_slider_images')->count())->toBe(2);
    expect($product->productItems->count())->toBe(4);

    $uploadedVariant = $product->productItems->firstWhere('sku', 'ABC-M-BLUE-COTTON');
    expect($uploadedVariant)->not->toBeNull();
    expect((int) $uploadedVariant->media_id)->toBeGreaterThan(0);
});

test('creating product with uploaded variant image marks variant as custom source', function () {
    $payload = basePayloadForSite($this->site);

    $size = Attribute::factory()->forSite($this->site)->create(['name' => 'Size', 'code' => 'size', 'order' => 1]);
    $color = Attribute::factory()->forSite($this->site)->create(['name' => 'Color', 'code' => 'color', 'order' => 2]);

    $payload['attributes'] = [
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
    ];
    $payload['variant_image_file_keys'] = ['S-BLUE'];
    $payload['variant_image_files'] = [UploadedFile::fake()->image('variant-only.jpg', 1000, 1000)];

    $response = $this->actingAs($this->user)
        ->post(route('products.store', ['site' => $this->site->slug]), $payload);

    $response->assertRedirect(route('products.index', ['site' => $this->site->slug]))
        ->assertSessionHas('success');

    $product = Product::query()->where('code', 'ABC')->firstOrFail();
    $customVariant = $product->productItems()->where('sku', 'ABC-S-BLUE')->firstOrFail();

    expect((int) $customVariant->media_id)->toBeGreaterThan(0);
    expect((bool) $customVariant->is_parent_image)->toBeFalse();
    expect((bool) $customVariant->is_parent_slider_image)->toBeFalse();
    expect($customVariant->getMedia('variant_images')->count())->toBe(1);
});
