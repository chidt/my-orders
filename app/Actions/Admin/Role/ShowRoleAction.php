<?php

namespace App\Actions\Admin\Role;

use App\Contracts\ActionContract;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class ShowRoleAction implements ActionContract
{
    public function handle(mixed ...$parameters): Response
    {
        /** @var Role $role */
        $role = $parameters[0];

        Gate::authorize('view', $role);

        $role->load(['permissions', 'users']);

        return Inertia::render('Admin/Roles/Show', [
            'role' => $role,
        ]);
    }

    public function __invoke(Role $role): Response
    {
        return $this->handle($role);
    }
}
