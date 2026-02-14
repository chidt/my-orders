<?php

namespace App\Actions\Admin\Permission;

use App\Contracts\ActionContract;

class DestroyPermissionAction implements ActionContract
{
    public function handle(mixed ...$parameters): bool
    {
        $permission = $parameters[0];

        return (bool) $permission->delete();
    }
}
