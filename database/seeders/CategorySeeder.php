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
                'description' => 'Các sản phẩm thời trang dành cho nam giới',
                'order' => 1,
                'parent_id' => null,
                'children' => [
                    ['name' => 'Áo Nam', 'description' => 'Áo sơ mi, áo thun, áo khoác nam', 'order' => 1],
                    ['name' => 'Quần Nam', 'description' => 'Quần jeans, quần tây, quần short nam', 'order' => 2],
                    ['name' => 'Phụ kiện Nam', 'description' => 'Thắt lưng, cà vạt, mũ nam', 'order' => 3],
                ],
            ],
            [
                'name' => 'Thời trang Nữ',
                'description' => 'Các sản phẩm thời trang dành cho nữ giới',
                'order' => 2,
                'parent_id' => null,
                'children' => [
                    ['name' => 'Áo Nữ', 'description' => 'Áo blouse, áo thun, áo dài nữ', 'order' => 1],
                    ['name' => 'Váy Đầm', 'description' => 'Váy công sở, đầm dự tiệc, đầm thường ngày', 'order' => 2],
                    ['name' => 'Quần Nữ', 'description' => 'Quần jeans, quần tây, quần culottes nữ', 'order' => 3],
                    ['name' => 'Phụ kiện Nữ', 'description' => 'Túi xách, trang sức, khăn choàng', 'order' => 4],
                ],
            ],
            [
                'name' => 'Thời trang Trẻ em',
                'description' => 'Các sản phẩm thời trang dành cho trẻ em',
                'order' => 3,
                'parent_id' => null,
                'children' => [
                    ['name' => 'Áo Trẻ em', 'description' => 'Áo thun, áo sơ mi trẻ em', 'order' => 1],
                    ['name' => 'Quần Trẻ em', 'description' => 'Quần jean, quần short trẻ em', 'order' => 2],
                    ['name' => 'Váy Bé gái', 'description' => 'Váy công chúa, đầm bé gái', 'order' => 3],
                ],
            ],
            [
                'name' => 'Hàng Thêu',
                'description' => 'Các sản phẩm thêu truyền thống và hiện đại',
                'order' => 4,
                'parent_id' => null,
                'children' => [
                    ['name' => 'Áo dài Thêu', 'description' => 'Áo dài truyền thống có họa tiết thêu', 'order' => 1],
                    ['name' => 'Túi Thêu', 'description' => 'Túi xách, ví có họa tiết thêu', 'order' => 2],
                    ['name' => 'Khăn Thêu', 'description' => 'Khăn choàng, khăn trang trí thêu tay', 'order' => 3],
                ],
            ],
            [
                'name' => 'Đồ lót & Đồ ngủ',
                'description' => 'Đồ lót và đồ ngủ cho nam nữ',
                'order' => 5,
                'parent_id' => null,
                'children' => [
                    ['name' => 'Đồ lót Nam', 'description' => 'Áo lót, quần lót nam', 'order' => 1],
                    ['name' => 'Đồ lót Nữ', 'description' => 'Áo ngực, quần lót nữ', 'order' => 2],
                    ['name' => 'Đồ ngủ', 'description' => 'Bộ đồ ngủ, váy ngủ', 'order' => 3],
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
