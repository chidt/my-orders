<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UnitsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            ['name' => 'Chiếc', 'unit' => 'chiếc'],
            ['name' => 'Bộ', 'unit' => 'bộ'],
            ['name' => 'Đôi', 'unit' => 'đôi'],
            ['name' => 'Cái', 'unit' => 'cái'],
            ['name' => 'Hộp', 'unit' => 'hộp'],
            ['name' => 'Con', 'unit' => 'con'],
            ['name' => 'Set', 'unit' => 'set'],
            ['name' => 'Combo', 'unit' => 'combo'],
        ];
        foreach ($units as $unit) {
            \App\Models\Unit::create($unit);
        }
    }
}
