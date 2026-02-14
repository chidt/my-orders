<?php

namespace App\Actions\Admin\Role;

use App\Contracts\ActionContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

class DestroyRoleAction implements ActionContract
{
    public function handle(mixed ...$parameters): RedirectResponse
    {
        /** @var Role $role */
        $role = $parameters[0];

        Gate::authorize('delete', $role);

        if ($role->users()->exists()) {
            return redirect()
                ->route('admin.roles.index')
                ->with('error', 'Không thể xoá vai trò đang sử dụng cho người dùng.');
        }

        $role->delete();

        return redirect()
            ->route('admin.roles.index')
            ->with('message', 'Xoá vai trò thành công.');
    }

    public function __invoke(Role $role): RedirectResponse
    {
        return $this->handle($role);
    }
}
