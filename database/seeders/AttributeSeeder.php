<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\Site;
use Illuminate\Database\Seeder;

class AttributeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sites = Site::all();

        $commonAttributes = [
            ['name' => 'Kích Thước', 'code' => 'size', 'description' => 'Kích thước sản phẩm', 'order' => 1],
            ['name' => 'Màu Sắc', 'code' => 'color', 'description' => 'Màu sắc sản phẩm', 'order' => 2],
            ['name' => 'Chất Liệu', 'code' => 'material', 'description' => 'Chất liệu sản phẩm', 'order' => 3],
            ['name' => 'Thương Hiệu', 'code' => 'brand', 'description' => 'Thương hiệu sản phẩm', 'order' => 4],
        ];

        foreach ($sites as $site) {
            foreach ($commonAttributes as $attributeData) {
                Attribute::firstOrCreate(
                    [
                        'code' => $attributeData['code'],
                        'site_id' => $site->id,
                    ],
                    [
                        'name' => $attributeData['name'],
                        'description' => $attributeData['description'],
                        'order' => $attributeData['order'],
                    ]
                );
            }
        }

        $this->command->info('Attributes seeded successfully.');
    }
}
