<?php

namespace App\Actions\Admin\Permission;

use App\Contracts\ActionContract;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;

class CreatePermissionAction implements ActionContract
{
    public function handle(mixed ...$parameters): Response
    {
        Gate::authorize('create', Permission::class);

        return Inertia::render('Admin/Permissions/Create');
    }

    public function __invoke(): Response
    {
        return $this->handle();
    }
}
