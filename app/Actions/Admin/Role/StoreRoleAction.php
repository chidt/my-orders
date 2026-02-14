<?php

namespace App\Actions\Admin\Role;

use App\Contracts\ActionContract;
use App\Http\Requests\Admin\StoreRoleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

class StoreRoleAction implements ActionContract
{
    public function handle(mixed ...$parameters): RedirectResponse
    {
        Gate::authorize('create', Role::class);
        /** @var StoreRoleRequest $request */
        $request = $parameters[0];

        $role = Role::create([
            'name' => $request->validated('name'),
            'guard_name' => 'web',
        ]);

        if ($request->filled('permissions')) {
            $role->syncPermissions($request->validated('permissions'));
        }

        return redirect()
            ->route('admin.roles.index')
            ->with('message', 'Tạo vai trò thành công.');
    }

    public function __invoke(StoreRoleRequest $request): RedirectResponse
    {
        return $this->handle($request);
    }
}
