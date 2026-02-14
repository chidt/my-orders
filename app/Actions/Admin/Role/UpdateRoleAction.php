<?php

namespace App\Actions\Admin\Role;

use App\Contracts\ActionContract;
use App\Http\Requests\Admin\UpdateRoleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

class UpdateRoleAction implements ActionContract
{
    public function handle(mixed ...$parameters): RedirectResponse
    {
        /** @var UpdateRoleRequest $request */
        /** @var Role $role */
        $request = $parameters[0];
        $role = $parameters[1];

        Gate::authorize('update', $role);

        $role->update([
            'name' => $request->validated('name'),
        ]);

        if ($request->filled('permissions')) {
            $role->syncPermissions($request->validated('permissions'));
        } else {
            $role->syncPermissions([]);
        }

        return redirect()
            ->route('admin.roles.index')
            ->with('message', 'Cập nhật vai trò thành công.');
    }

    public function __invoke(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        return $this->handle($request, $role);
    }
}
