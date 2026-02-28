<?php

namespace App\Actions\Admin\Permission;

use App\Contracts\ActionContract;
use Spatie\Permission\Models\Permission;

class ShowPermissionAction implements ActionContract
{
    public function handle(mixed ...$parameters): array
    {
        $permission = $parameters[0];

        return [
            'permission' => $permission,
            'can' => [
                'edit' => auth()->user()->can('edit_permissions'),
                'delete' => auth()->user()->can('delete_permissions'),
            ],
        ];
    }

    public function __invoke(Permission $permission): array
    {
        return $this->handle($permission);
    }
}
