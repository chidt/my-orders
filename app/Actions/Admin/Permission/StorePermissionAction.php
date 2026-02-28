<?php

namespace App\Actions\Admin\Permission;

use App\Contracts\ActionContract;
use App\Http\Requests\Admin\StorePermissionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;

class StorePermissionAction implements ActionContract
{
    public function handle(mixed ...$parameters): RedirectResponse
    {
        Gate::authorize('create', Permission::class);
        /** @var StorePermissionRequest $request */
        $request = $parameters[0];

        Permission::create([
            'name' => $request->validated('name'),
            'guard_name' => 'web',
        ]);

        return redirect()
            ->route('admin.permissions.index')
            ->with('message', 'Tạo quyền hạn thành công.');
    }

    public function __invoke(StorePermissionRequest $request): RedirectResponse
    {
        return $this->handle($request);
    }
}
