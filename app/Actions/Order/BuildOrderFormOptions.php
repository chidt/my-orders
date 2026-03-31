<?php

namespace App\Actions\Order;

use App\Models\Customer;
use App\Models\Province;
use App\Models\Site;

class BuildOrderFormOptions
{
    public function execute(Site $site): array
    {
        return [
            'customerTypes' => Customer::typeOptions(),
            'provinces' => Province::query()->orderBy('name')->get(['id', 'name']),
        ];
    }
}
