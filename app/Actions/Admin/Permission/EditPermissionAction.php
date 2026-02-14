<?php

namespace App\Actions\Admin\Permission;

use App\Contracts\ActionContract;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;

class EditPermissionAction implements ActionContract
{
    public function handle(mixed ...$parameters): Response
    {
        /** @var Permission $permission */
        $permission = $parameters[0];

        Gate::authorize('update', $permission);

        return Inertia::render('Admin/Permissions/Edit', [
            'permission' => $permission,
        ]);
    }

    public function __invoke(Permission $permission): Response
    {
        return $this->handle($permission);
    }
}
