<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Site;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            // Root categories
            [
                'name' => 'Thời trang Nam',
                'slug' => 'thoi-trang-nam',
                'description' => 'Các sản phẩm thời trang dành cho nam giới',
                'order' => 1,
                'is_active' => true,
                'parent_id' => null,
                'children' => [
                    ['name' => 'Áo Nam', 'slug' => 'ao-nam', 'description' => 'Áo sơ mi, áo thun, áo khoác nam', 'order' => 1, 'is_active' => true],
                    ['name' => 'Quần Nam', 'slug' => 'quan-nam', 'description' => 'Quần jeans, quần tây, quần short nam', 'order' => 2, 'is_active' => true],
                    ['name' => 'Phụ kiện Nam', 'slug' => 'phu-kien-nam', 'description' => 'Thắt lưng, cà vạt, mũ nam', 'order' => 3, 'is_active' => true],
                ],
            ],
            [
                'name' => 'Thời trang Nữ',
                'slug' => 'thoi-trang-nu',
                'description' => 'Các sản phẩm thời trang dành cho nữ giới',
                'order' => 2,
                'is_active' => true,
                'parent_id' => null,
                'children' => [
                    ['name' => 'Áo Nữ', 'slug' => 'ao-nu', 'description' => 'Áo blouse, áo thun, áo dài nữ', 'order' => 1, 'is_active' => true],
                    ['name' => 'Váy Đầm', 'slug' => 'vay-dam', 'description' => 'Váy công sở, đầm dự tiệc, đầm thường ngày', 'order' => 2, 'is_active' => true],
                    ['name' => 'Quần Nữ', 'slug' => 'quan-nu', 'description' => 'Quần jeans, quần tây, quần culottes nữ', 'order' => 3, 'is_active' => true],
                    ['name' => 'Phụ kiện Nữ', 'slug' => 'phu-kien-nu', 'description' => 'Túi xách, trang sức, khăn choàng', 'order' => 4, 'is_active' => true],
                ],
            ],
            [
                'name' => 'Thời trang Trẻ em',
                'slug' => 'thoi-trang-tre-em',
                'description' => 'Các sản phẩm thời trang dành cho trẻ em',
                'order' => 3,
                'is_active' => true,
                'parent_id' => null,
                'children' => [
                    ['name' => 'Áo Trẻ em', 'slug' => 'ao-tre-em', 'description' => 'Áo thun, áo sơ mi trẻ em', 'order' => 1, 'is_active' => true],
                    ['name' => 'Quần Trẻ em', 'slug' => 'quan-tre-em', 'description' => 'Quần jean, quần short trẻ em', 'order' => 2, 'is_active' => true],
                    ['name' => 'Váy Bé gái', 'slug' => 'vay-be-gai', 'description' => 'Váy công chúa, đầm bé gái', 'order' => 3, 'is_active' => true],
                ],
            ],
            [
                'name' => 'Hàng Thêu',
                'slug' => 'hang-theu',
                'description' => 'Các sản phẩm thêu truyền thống và hiện đại',
                'order' => 4,
                'is_active' => true,
                'parent_id' => null,
                'children' => [
                    ['name' => 'Áo dài Thêu', 'slug' => 'ao-dai-theu', 'description' => 'Áo dài truyền thống có họa tiết thêu', 'order' => 1, 'is_active' => true],
                    ['name' => 'Túi Thêu', 'slug' => 'tui-theu', 'description' => 'Túi xách, ví có họa tiết thêu', 'order' => 2, 'is_active' => true],
                    ['name' => 'Khăn Thêu', 'slug' => 'khan-theu', 'description' => 'Khăn choàng, khăn trang trí thêu tay', 'order' => 3, 'is_active' => true],
                ],
            ],
            [
                'name' => 'Đồ lót & Đồ ngủ',
                'slug' => 'do-lot-do-ngu',
                'description' => 'Đồ lót và đồ ngủ cho nam nữ',
                'order' => 5,
                'is_active' => true,
                'parent_id' => null,
                'children' => [
                    ['name' => 'Đồ lót Nam', 'slug' => 'do-lot-nam', 'description' => 'Áo lót, quần lót nam', 'order' => 1, 'is_active' => true],
                    ['name' => 'Đồ lót Nữ', 'slug' => 'do-lot-nu', 'description' => 'Áo ngực, quần lót nữ', 'order' => 2, 'is_active' => true],
                    ['name' => 'Đồ ngủ', 'slug' => 'do-ngu', 'description' => 'Bộ đồ ngủ, váy ngủ', 'order' => 3, 'is_active' => true],
                ],
            ],
        ];

        // Get all sites to create categories for each site
        $sites = Site::all();

        if ($sites->isEmpty()) {
            // If no sites exist, create categories without site_id (will be null)
            $this->createCategories($categories, null);
        } else {
            // Create categories for each site
            foreach ($sites as $site) {
                $this->createCategories($categories, $site->id);
            }
        }
    }

    /**
     * Create categories with hierarchy
     */
    private function createCategories(array $categories, $siteId): void
    {
        foreach ($categories as $categoryData) {
            // Create parent category
            $children = $categoryData['children'] ?? [];
            unset($categoryData['children']);

            $parentCategory = Category::create(array_merge($categoryData, ['site_id' => $siteId]));

            // Create child categories
            foreach ($children as $childData) {
                Category::create(array_merge($childData, [
                    'parent_id' => $parentCategory->id,
                    'site_id' => $siteId,
                ]));
            }
        }
    }
}
