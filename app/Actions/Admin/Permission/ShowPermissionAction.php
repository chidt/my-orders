<?php

namespace App\Actions\Admin\Permission;

use App\Contracts\ActionContract;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;

class ShowPermissionAction implements ActionContract
{
    public function handle(mixed ...$parameters): array
    {
        /** @var Permission $permission */
        $permission = $parameters[0];

        Gate::authorize('view', $permission);

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
