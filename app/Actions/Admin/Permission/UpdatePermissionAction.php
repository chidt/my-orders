<?php

namespace App\Actions\Admin\Permission;

use App\Contracts\ActionContract;
use Illuminate\Http\Request;
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

    public function __invoke(Request $request, Permission $permission): Permission
    {
        return $this->handle($request, $permission);
    }
}
