<?php

namespace App\Actions\Admin\Permission;

use App\Contracts\ActionContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;

class DestroyPermissionAction implements ActionContract
{
    public function handle(mixed ...$parameters): RedirectResponse
    {
        /** @var Permission $permission */
        $permission = $parameters[0];

        Gate::authorize('delete', $permission);

        if ($permission->roles()->exists()) {
            return redirect()
                ->route('admin.permissions.index')
                ->with('error', 'Không thể xoá quyền hạn đang sử dụng cho vai trò.');
        }

        $permission->delete();

        return redirect()
            ->route('admin.permissions.index')
            ->with('message', 'Xoá quyền hạn thành công.');
    }

    public function __invoke(Permission $permission): RedirectResponse
    {
        return $this->handle($permission);
    }
}
