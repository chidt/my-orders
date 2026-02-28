<?php

namespace App\Actions\Admin\Permission;

use App\Contracts\ActionContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;

class UpdatePermissionAction implements ActionContract
{
    public function handle(mixed ...$parameters): RedirectResponse
    {
        $request = $parameters[0];
        /** @var Permission $permission */
        $permission = $parameters[1];

        Gate::authorize('update', $permission);

        $permission->update([
            'name' => $request->validated('name'),
        ]);

        return redirect()
            ->route('admin.permissions.index')
            ->with('message', 'Cập nhật quyền hạn thành công.');
    }

    public function __invoke(Request $request, Permission $permission): RedirectResponse
    {
        return $this->handle($request, $permission);
    }
}
