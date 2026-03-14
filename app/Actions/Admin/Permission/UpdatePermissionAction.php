<?php

namespace App\Actions\Admin\Permission;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class UpdatePermissionAction
{
    public function update(Request $request, Permission $permission): Permission
    {
        $permission->update([
            'name' => $request->validated('name'),
        ]);

        return $permission;
    }
}
