<?php

namespace App\Actions\Admin\Permission;

use App\Contracts\ActionContract;
use Spatie\Permission\Models\Permission;

class UpdatePermissionAction implements ActionContract
{
    public function handle(mixed ...$parameters): Permission
    {
        $request = $parameters[0];
        $permission = $parameters[1];

        $permission->update([
            'name' => $request->validated('name'),
        ]);

        return $permission;
    }
}
