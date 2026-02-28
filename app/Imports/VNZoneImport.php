<?php

namespace App\Imports;

use App\Models\Province;
use App\Models\Ward;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class VNZoneImport implements ToModel, WithHeadingRow
{
    /**
     * @return Model|null
     */
    public function model(array $row)
    {
        $province = Province::firstOrCreate(
            ['gso_id' => $row['ma_tp']],
            ['name' => $row['tinh_thanh_pho']]
        );

        Ward::firstOrCreate(
            [
                'gso_id' => $row['ma'],
            ],
            [
                'name' => $row['ten'],
                'province_id' => $province->id,
            ]
        );
    }
}
