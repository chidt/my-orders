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
        ];
    }

    public function __invoke(Permission $permission): array
    {
        return $this->handle($permission);
    }
}
