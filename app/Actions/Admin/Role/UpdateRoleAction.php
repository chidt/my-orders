<?php

namespace App\Actions\Admin\Role;

use App\Http\Requests\Admin\UpdateRoleRequest;
use Spatie\Permission\Models\Role;

class UpdateRoleAction
{
    public function update(UpdateRoleRequest $request, Role $role): Role
    {
        $role->update([
            'name' => $request->validated('name'),
        ]);

        if ($request->filled('permissions')) {
            $role->syncPermissions($request->validated('permissions'));
        } else {
            $role->syncPermissions([]);
        }

        return $role;
    }
}
