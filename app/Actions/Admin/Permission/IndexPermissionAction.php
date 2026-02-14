<?php

namespace App\Actions\Admin\Permission;

use App\Contracts\ActionContract;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;

class IndexPermissionAction implements ActionContract
{
    public function handle(mixed ...$parameters): array
    {
        Gate::authorize('viewAny', Permission::class);

        $permissions = Permission::query()
            ->orderBy('name')
            ->paginate(100);

        return [
            'permissions' => $permissions,
            'can' => [
                'create' => auth()->user()->can('create_permissions'),
                'edit' => auth()->user()->can('edit_permissions'),
                'delete' => auth()->user()->can('delete_permissions'),
            ],
        ];
    }

    public function __invoke(): array
    {
        return $this->handle();
    }
}
