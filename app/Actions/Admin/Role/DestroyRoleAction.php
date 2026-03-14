<?php

namespace App\Actions\Admin\Role;

use Spatie\Permission\Models\Role;

class DestroyRoleAction
{
    public function destroy(Role $role): bool
    {
        if ($role->users()->exists()) {
            return false;
        }

        return $role->delete();
    }
}
