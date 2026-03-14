<?php

namespace App\Actions\Admin\Role;

use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\Permission\Models\Role;

class IndexRoleAction
{
    public function index(): LengthAwarePaginator
    {
        return Role::query()
            ->with(['permissions'])
            ->withCount(['users'])
            ->orderBy('name')
            ->paginate(15);
    }
}
