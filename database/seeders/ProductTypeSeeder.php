<?php

namespace Database\Seeders;

use App\Models\ProductType;
use App\Models\Site;
use Illuminate\Database\Seeder;

class ProductTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $colors = [
            '#3B82F6', // Blue
            '#EF4444', // Red
            '#10B981', // Green
            '#F59E0B', // Yellow
            '#8B5CF6', // Purple
            '#F97316', // Orange
            '#EC4899', // Pink
            '#6B7280', // Gray
            '#14B8A6', // Teal
            '#84CC16', // Lime
        ];

        $productTypes = [
            'Quảng Châu',
            'VNTK',
            'Hàng Thêu',
        ];

        // Get all sites to create product types for each site
        $sites = Site::all();

        if ($sites->isEmpty()) {
            // If no sites exist, create product types without site_id (will be null)
            foreach ($productTypes as $index => $name) {
                ProductType::create([
                    'name' => $name,
                    'order' => $index + 1,
                    'show_on_front' => true,
                    'color' => $colors[array_rand($colors)],
                    'site_id' => null,
                ]);
            }
        } else {
            // Create product types for each site
            foreach ($sites as $site) {
                foreach ($productTypes as $index => $name) {
                    ProductType::create([
                        'name' => $name,
                        'order' => $index + 1,
                        'show_on_front' => true,
                        'color' => $colors[array_rand($colors)],
                        'site_id' => $site->id,
                    ]);
                }
            }
        }
    }
}
