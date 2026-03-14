<?php

namespace App\Actions\Admin\Permission;

use Spatie\Permission\Models\Permission;

class DestroyPermissionAction
{
    public function destroy(Permission $permission): bool
    {
        // Check if permission is assigned to any roles
        if ($permission->roles()->exists()) {
            return false;
        }

        return $permission->delete();
    }
}
