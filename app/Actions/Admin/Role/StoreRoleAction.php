<?php

namespace App\Actions\Admin\Role;

use App\Http\Requests\Admin\StoreRoleRequest;
use Spatie\Permission\Models\Role;

class StoreRoleAction
{
    public function store(StoreRoleRequest $request): Role
    {
        $role = Role::create([
            'name' => $request->validated('name'),
            'guard_name' => 'web',
        ]);

        if ($request->filled('permissions')) {
            $role->syncPermissions($request->validated('permissions'));
        }

        return $role;
    }
}
