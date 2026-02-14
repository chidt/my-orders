<?php

namespace App\Actions\Admin\Role;

use App\Contracts\ActionContract;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class EditRoleAction implements ActionContract
{
    public function handle(mixed ...$parameters): Response
    {
        /** @var Role $role */
        $role = $parameters[0];

        Gate::authorize('update', $role);

        $permissions = Permission::orderBy('name')->get();
        $role->load('permissions');

        return Inertia::render('Admin/Roles/Edit', [
            'role' => $role,
            'permissions' => $permissions,
        ]);
    }

    public function __invoke(Role $role): Response
    {
        return $this->handle($role);
    }
}
