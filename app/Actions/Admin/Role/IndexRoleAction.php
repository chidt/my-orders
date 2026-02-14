<?php

namespace App\Actions\Admin\Role;

use App\Contracts\ActionContract;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class IndexRoleAction implements ActionContract
{
    public function handle(mixed ...$parameters): Response
    {
        Gate::authorize('viewAny', Role::class);

        $roles = Role::query()
            ->with(['permissions'])
            ->withCount(['users'])
            ->orderBy('name')
            ->paginate(15);

        return Inertia::render('Admin/Roles/Index', [
            'roles' => $roles,
        ]);
    }

    public function __invoke(): Response
    {
        return $this->handle();
    }
}
