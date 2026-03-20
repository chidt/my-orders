<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Location templates for each warehouse
        $locationTemplates = [
            [
                'code' => 'A001',
                'name' => 'Khu A - Tầng 1',
                'is_default' => true,
                'qty_in_stock' => 0,
            ],
            [
                'code' => 'A002',
                'name' => 'Khu A - Tầng 2',
                'is_default' => false,
                'qty_in_stock' => 0,
            ],
            [
                'code' => 'B001',
                'name' => 'Khu B - Tầng 1',
                'is_default' => false,
                'qty_in_stock' => 0,
            ],
            [
                'code' => 'B002',
                'name' => 'Khu B - Tầng 2',
                'is_default' => false,
                'qty_in_stock' => 0,
            ],
            [
                'code' => 'C001',
                'name' => 'Khu C - Hàng nhanh',
                'is_default' => false,
                'qty_in_stock' => 0,
            ],
            [
                'code' => 'D001',
                'name' => 'Khu D - Hàng chậm',
                'is_default' => false,
                'qty_in_stock' => 0,
            ],
            [
                'code' => 'E001',
                'name' => 'Khu E - Hàng xuất khẩu',
                'is_default' => false,
                'qty_in_stock' => 0,
            ],
            [
                'code' => 'RECV',
                'name' => 'Khu nhận hàng',
                'is_default' => false,
                'qty_in_stock' => 0,
            ],
            [
                'code' => 'SHIP',
                'name' => 'Khu xuất hàng',
                'is_default' => false,
                'qty_in_stock' => 0,
            ],
        ];

        // Get all warehouses to create locations for each warehouse
        $warehouses = Warehouse::all();

        foreach ($warehouses as $warehouse) {
            $isFirstLocation = true;
            foreach ($locationTemplates as $locationData) {
                Location::create(array_merge($locationData, [
                    'warehouse_id' => $warehouse->id,
                    // Only the first location should be default for each warehouse
                    'is_default' => $isFirstLocation ? true : $locationData['is_default'],
                ]));
                $isFirstLocation = false;
            }
        }
    }
}
