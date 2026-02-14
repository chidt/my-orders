<?php

namespace App\Actions\Admin\Permission;

use App\Contracts\ActionContract;
use Spatie\Permission\Models\Permission;

class StorePermissionAction implements ActionContract
{
    public function handle(mixed ...$parameters): Permission
    {
        $request = $parameters[0];

        return Permission::create([
            'name' => $request->validated('name'),
            'guard_name' => 'web',
        ]);
    }
}
