<?php

namespace App\Actions\Warehouse;

use App\Models\Site;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListWarehouses
{
    public function execute(Site $site): LengthAwarePaginator
    {
        return $site->warehouses()
            ->withLocationsCount()
            ->orderBy('name')
            ->paginate(15);
    }
}
