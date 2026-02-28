<?php

namespace App\Actions\Admin\Permission;

use App\Contracts\ActionContract;
use Spatie\Permission\Models\Permission;

class DestroyPermissionAction implements ActionContract
{
    public function handle(mixed ...$parameters): bool
    {
        $permission = $parameters[0];

        return (bool) $permission->delete();
    }

    public function __invoke(Permission $permission): bool
    {
        return $this->handle($permission);
    }
}
