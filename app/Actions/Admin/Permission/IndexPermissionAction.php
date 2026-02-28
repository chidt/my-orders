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
        ];
    }

    public function __invoke(): array
    {
        return $this->handle();
    }
}
