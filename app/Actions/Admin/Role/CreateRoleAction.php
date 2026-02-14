<?php

namespace App\Actions\Admin\Role;

use App\Contracts\ActionContract;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CreateRoleAction implements ActionContract
{
    public function handle(mixed ...$parameters): Response
    {
        Gate::authorize('create', Role::class);

        $permissions = Permission::orderBy('name')->get();

        return Inertia::render('Admin/Roles/Create', [
            'permissions' => $permissions,
        ]);
    }

    public function __invoke(): Response
    {
        return $this->handle();
    }
}
