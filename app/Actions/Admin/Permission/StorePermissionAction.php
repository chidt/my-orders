<?php

namespace App\Actions\Admin\Permission;

use App\Http\Requests\Admin\StorePermissionRequest;
use Spatie\Permission\Models\Permission;

class StorePermissionAction
{
    public function store(StorePermissionRequest $request): Permission
    {
        return Permission::create([
            'name' => $request->validated('name'),
            'guard_name' => 'web',
        ]);
    }
}
