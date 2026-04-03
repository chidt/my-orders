<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductAttributeValue;
use App\Models\ProductItem;
use App\Models\ProductItemAttributeValue;
use App\Models\ProductType;
use App\Models\Site;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ProductSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting Product seeding...');

        // Get all sites to seed products for each
        $sites = Site::all();

        if ($sites->isEmpty()) {
            $this->command->warn('⚠️ No sites found. Please run SiteSeeder first.');

            return;
        }

        foreach ($sites as $site) {
            $this->seedProductsForSite($site);
        }

        $this->command->info('✅ Product seeding completed!');
    }

    private function seedProductsForSite(Site $site): void
    {
        $this->command->info("🏪 Seeding products for site: {$site->name}");

        // Get required dependencies for this site
        $categories = Category::where('site_id', $site->id)->get();
        $suppliers = Supplier::where('site_id', $site->id)->get();
        $productTypes = ProductType::where('site_id', $site->id)->get();
        $attributes = Attribute::where('site_id', $site->id)->get();
        $locations = Location::whereHas('warehouse', function ($query) use ($site) {
            $query->where('site_id', $site->id);
        })->get();
        $units = Unit::all();

        if ($categories->isEmpty() || $suppliers->isEmpty() || $locations->isEmpty()) {
            $this->command->warn("⚠️ Missing dependencies for site {$site->name}. Skipping...");

            return;
        }

        // Product data templates
        $productTemplates = [
            [
                'name' => 'Giày bé gái A001',
                'code' => 'A001',
                'description' => 'Giày thời trang cho bé gái, thiết kế xinh xắn và tiện dụng',
                'base_price' => 150000,
                'product_type_name' => 'Quảng Châu',
                'attributes' => ['Kích Thước', 'Màu Sắc'],
                'folder' => '1',
            ],
            [
                'name' => 'Áo thêu lá cờ A002',
                'code' => 'A002',
                'description' => 'Áo thêu hình lá cờ đẹp mắt, chất lượng cao cấp',
                'base_price' => 350000,
                'product_type_name' => 'Hàng Thêu',
                'attributes' => ['Kích Thước', 'Màu Sắc'],
                'folder' => '2',
            ],
            [
                'name' => 'Váy premium A003',
                'code' => 'A003',
                'description' => 'Váy cao cấp thiết kế sang trọng, phong cách hiện đại',
                'base_price' => 800000,
                'product_type_name' => 'VNTK',
                'attributes' => ['Kích Thước', 'Màu Sắc'],
                'folder' => '3',
            ],
            [
                'name' => 'Váy thêu ngựa A004',
                'code' => 'A004',
                'description' => 'Váy thêu hình ngựa xinh đẹp, thích hợp cho bé gái',
                'base_price' => 450000,
                'product_type_name' => 'Quảng Châu',
                'attributes' => ['Màu Sắc'],
                'folder' => '4',
            ],
        ];

        DB::transaction(function () use ($site, $categories, $suppliers, $productTypes, $locations, $units, $attributes, $productTemplates) {
            foreach ($productTemplates as $index => $template) {
                $this->createProductWithVariants($site, $template, $categories, $suppliers, $productTypes, $locations, $units, $attributes, $index + 1);
            }
        });
    }

    private function createProductWithVariants(
        Site $site,
        array $template,
        $categories,
        $suppliers,
        $productTypes,
        $locations,
        $units,
        $attributes,
        int $productNumber
    ): void {
        $this->command->info("   📦 Creating product: {$template['name']}");

        // Create base product
        $productType = $productTypes->where('name', $template['product_type_name'])->first();
        if (! $productType) {
            // Fallback to first product type if not found
            $productType = $productTypes->first();
            $this->command->warn("   ⚠️ Product type '{$template['product_type_name']}' not found, using '{$productType->name}'");
        }

        $product = Product::create([
            'name' => $template['name'],
            'code' => $template['code'], // Use custom code from template
            'supplier_code' => 'SUP-'.fake()->numerify('###'),
            'product_type_id' => $productType->id,
            'description' => $template['description'],
            'qty_in_stock' => 0,
            'weight' => fake()->randomFloat(2, 0.5, 2.0),
            'price' => $template['base_price'],
            'partner_price' => $template['base_price'] * 0.9,
            'purchase_price' => $template['base_price'] * 0.6,
            'supplier_id' => $suppliers->random()->id,
            'unit_id' => $units->random()->id,
            'category_id' => $categories->random()->id,
            'order_closing_date' => fake()->optional(0.3)->dateTimeBetween('now', '+30 days'),
            'default_location_id' => $locations->random()->id,
            'site_id' => $site->id,
        ]);

        // Add product images
        $this->addProductMedia($product, $template['folder']);

        // Create attributes and attribute values
        $productAttributes = [];
        foreach ($template['attributes'] as $attributeName) {
            $attribute = $attributes->where('name', $attributeName)->first();
            if (! $attribute) {
                continue;
            }

            $productAttributes[$attribute->id] = $this->createProductAttributeValues($product, $attribute);
        }

        // Create product variants (ProductItems)
        if (! empty($productAttributes)) {
            $this->createProductVariants($product, $productAttributes, $site->id);
        } else {
            // Create a simple product item if no attributes
            $this->createSimpleProductItem($product, $site->id);
        }

        $this->command->info("   ✅ Created product: {$template['name']} with variants");
    }

    private function addProductMedia(Product $product, string $folder): void
    {
        $imagePath = database_path("data/product/{$folder}");

        if (! File::exists($imagePath)) {
            $this->command->warn("   ⚠️ Image folder not found: {$imagePath}");

            return;
        }

        // Add main image - copy file to preserve original
        $mainImagePath = "{$imagePath}/main.jpg";
        if (File::exists($mainImagePath)) {
            // Copy file to temporary location to preserve original
            $tempPath = storage_path('app/temp_'.uniqid().'_main.jpg');
            File::copy($mainImagePath, $tempPath);

            $media = $product
                ->addMedia($tempPath)
                ->usingName('main.jpg')
                ->usingFileName('main_'.$product->code.'.jpg')
                ->toMediaCollection('main_image');

            // Clean up temporary file
            File::delete($tempPath);

            $product->update(['media_id' => $media->id]);
            $this->command->info('     📸 Added main image: main.jpg');
        }

        // Add slider images - copy files to preserve originals
        $sliderImages = ['1.jpg', '2.jpg', '3.jpg', '4.jpg'];
        foreach ($sliderImages as $imageName) {
            $imageFilePath = "{$imagePath}/{$imageName}";
            if (File::exists($imageFilePath)) {
                // Copy file to temporary location to preserve original
                $tempPath = storage_path('app/temp_'.uniqid().'_'.$imageName);
                File::copy($imageFilePath, $tempPath);

                $product
                    ->addMedia($tempPath)
                    ->usingName($imageName)
                    ->usingFileName('slider_'.$product->code.'_'.$imageName)
                    ->toMediaCollection('product_slider_images');

                // Clean up temporary file
                File::delete($tempPath);

                $this->command->info("     📸 Added slider image: {$imageName}");
            }
        }
    }

    private function createProductAttributeValues(Product $product, Attribute $attribute): array
    {
        $values = [];

        switch ($attribute->name) {
            case 'Kích Thước':
                $sizeData = [
                    ['code' => 'S', 'value' => 'Small', 'addition_value' => 0],
                    ['code' => 'M', 'value' => 'Medium', 'addition_value' => 0],
                    ['code' => 'L', 'value' => 'Large', 'addition_value' => 10000],
                    ['code' => 'XL', 'value' => 'Extra Large', 'addition_value' => 15000],
                ];

                foreach ($sizeData as $index => $data) {
                    $value = ProductAttributeValue::create([
                        'code' => $data['code'],
                        'value' => $data['value'],
                        'order' => $index + 1,
                        'addition_value' => $data['addition_value'],
                        'partner_addition_value' => $data['addition_value'] * 0.8,
                        'purchase_addition_value' => $data['addition_value'] * 0.5,
                        'product_id' => $product->id,
                        'attribute_id' => $attribute->id,
                    ]);
                    $values[] = $value;
                }
                break;

            case 'Màu Sắc':
                $colorData = [
                    ['code' => 'BLACK', 'value' => 'Đen', 'addition_value' => 0],
                    ['code' => 'WHITE', 'value' => 'Trắng', 'addition_value' => 0],
                    ['code' => 'BLUE', 'value' => 'Xanh dương', 'addition_value' => 5000],
                    ['code' => 'RED', 'value' => 'Đỏ', 'addition_value' => 8000],
                ];

                foreach ($colorData as $index => $data) {
                    $value = ProductAttributeValue::create([
                        'code' => $data['code'],
                        'value' => $data['value'],
                        'order' => $index + 1,
                        'addition_value' => $data['addition_value'],
                        'partner_addition_value' => $data['addition_value'] * 0.8,
                        'purchase_addition_value' => $data['addition_value'] * 0.5,
                        'product_id' => $product->id,
                        'attribute_id' => $attribute->id,
                    ]);
                    $values[] = $value;
                }
                break;
        }

        return $values;
    }

    private function createProductVariants(Product $product, array $productAttributes, int $siteId): void
    {
        // Get combinations of all attributes
        $combinations = $this->getAttributeCombinations($productAttributes);

        foreach ($combinations as $combination) {
            $sku = $this->generateSKU($product->code, $combination);
            $name = $this->generateVariantName($product->name, $combination);
            $prices = $this->calculateVariantPrices($product, $combination);

            $productItem = ProductItem::create([
                'name' => $name,
                'sku' => $sku,
                'is_parent_image' => true,
                'is_parent_slider_image' => false,
                'qty_in_stock' => 0,
                'price' => $prices['price'],
                'partner_price' => $prices['partner_price'],
                'purchase_price' => $prices['purchase_price'],
                'media_id' => $product->media_id,
                'product_id' => $product->id,
                'site_id' => $siteId,
            ]);

            // Create ProductItemAttributeValue relationships
            foreach ($combination as $attributeValue) {
                ProductItemAttributeValue::create([
                    'product_item_id' => $productItem->id,
                    'product_attribute_value_id' => $attributeValue->id,
                ]);
            }

            $this->command->info("     🔗 Created variant: {$name} ({$sku})");
        }
    }

    private function createSimpleProductItem(Product $product, int $siteId): void
    {
        ProductItem::create([
            'name' => $product->name,
            'sku' => $product->code,
            'is_parent_image' => true,
            'is_parent_slider_image' => false,
            'qty_in_stock' => 0,
            'price' => $product->price,
            'partner_price' => $product->partner_price,
            'purchase_price' => $product->purchase_price,
            'media_id' => $product->media_id,
            'product_id' => $product->id,
            'site_id' => $siteId,
        ]);
    }

    private function getAttributeCombinations(array $productAttributes): array
    {
        $combinations = [[]];

        foreach ($productAttributes as $attributeId => $values) {
            $newCombinations = [];
            foreach ($combinations as $combination) {
                foreach ($values as $value) {
                    $newCombinations[] = array_merge($combination, [$value]);
                }
            }
            $combinations = $newCombinations;
        }

        return $combinations;
    }

    private function generateSKU(string $productCode, array $combination): string
    {
        $codes = collect($combination)->pluck('code')->toArray();

        return $productCode.'-'.implode('-', $codes);
    }

    private function generateVariantName(string $productName, array $combination): string
    {
        $values = collect($combination)->pluck('value')->toArray();

        return $productName.' - '.implode(' - ', $values);
    }

    private function calculateVariantPrices(Product $product, array $combination): array
    {
        $basePrice = (float) $product->price;
        $basePartnerPrice = (float) $product->partner_price;
        $basePurchasePrice = (float) $product->purchase_price;

        $addition = collect($combination)->sum('addition_value');
        $partnerAddition = collect($combination)->sum('partner_addition_value');
        $purchaseAddition = collect($combination)->sum('purchase_addition_value');

        return [
            'price' => $basePrice + $addition,
            'partner_price' => $basePartnerPrice ? $basePartnerPrice + $partnerAddition : null,
            'purchase_price' => $basePurchasePrice + $purchaseAddition,
        ];
    }
}
