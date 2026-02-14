<?php

namespace App\Actions\Admin\Permission;

use App\Contracts\ActionContract;
use Spatie\Permission\Models\Permission;

class IndexPermissionAction implements ActionContract
{
    public function handle(mixed ...$parameters): array
    {
        $permissions = Permission::query()
            ->orderBy('name')
            ->paginate(15);

        return [
            'permissions' => $permissions,
            'can' => [
                'create' => auth()->user()->can('create_permissions'),
                'edit' => auth()->user()->can('edit_permissions'),
                'delete' => auth()->user()->can('delete_permissions'),
            ],
        ];
    }
}
