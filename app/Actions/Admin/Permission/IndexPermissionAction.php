<?php

namespace App\Actions\Admin\Permission;

use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\Permission\Models\Permission;

class IndexPermissionAction
{
    public function index(): LengthAwarePaginator
    {
        return Permission::query()
            ->orderBy('name')
            ->paginate(100);
    }
}
