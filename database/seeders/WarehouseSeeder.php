<?php

namespace Database\Seeders;

use App\Models\Site;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $warehouses = [
            [
                'code' => 'WH001',
                'name' => 'Kho Trung tâm TP.HCM',
                'address' => 'Số 123 Đường Nguyễn Văn Linh, Quận 7, TP. Hồ Chí Minh',
            ],
            [
                'code' => 'WH002',
                'name' => 'Kho Miền Bắc',
                'address' => 'Số 456 Đường Giải Phóng, Hai Bà Trưng, Hà Nội',
            ],
            [
                'code' => 'WH003',
                'name' => 'Kho Miền Trung',
                'address' => 'Số 789 Đường Hùng Vương, Hải Châu, Đà Nẵng',
            ],
            [
                'code' => 'WH004',
                'name' => 'Kho Tân Bình',
                'address' => 'Số 321 Đường Cộng Hòa, Tân Bình, TP. Hồ Chí Minh',
            ],
            [
                'code' => 'WH005',
                'name' => 'Kho Xuất khẩu',
                'address' => 'Khu công nghiệp Tân Thuận, Quận 7, TP. Hồ Chí Minh',
            ],
            [
                'code' => 'WH006',
                'name' => 'Kho Bình Dương',
                'address' => 'Khu công nghiệp Việt Nam - Singapore, Thuận An, Bình Dương',
            ],
        ];

        // Get all sites to create warehouses for each site
        $sites = Site::all();

        if ($sites->isEmpty()) {
            // If no sites exist, create warehouses without site_id (will be null)
            foreach ($warehouses as $warehouseData) {
                Warehouse::create(array_merge($warehouseData, ['site_id' => null]));
            }
        } else {
            // Create warehouses for each site
            foreach ($sites as $site) {
                foreach ($warehouses as $warehouseData) {
                    Warehouse::create(array_merge($warehouseData, ['site_id' => $site->id]));
                }
            }
        }
    }
}
