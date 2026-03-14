<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Permission\DestroyPermissionAction;
use App\Actions\Admin\Permission\IndexPermissionAction;
use App\Actions\Admin\Permission\StorePermissionAction;
use App\Actions\Admin\Permission\UpdatePermissionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePermissionRequest;
use App\Http\Requests\Admin\UpdatePermissionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index(IndexPermissionAction $action): Response
    {
        Gate::authorize('viewAny', Permission::class);

        $permissions = $action->index();

        return Inertia::render('Admin/Permissions/Index', [
            'permissions' => $permissions,
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Permission::class);

        return Inertia::render('Admin/Permissions/Create');
    }

    public function store(StorePermissionRequest $request, StorePermissionAction $action): RedirectResponse
    {
        Gate::authorize('create', Permission::class);

        $action->store($request);

        return redirect()
            ->route('admin.permissions.index')
            ->with('message', 'Tạo quyền hạn thành công.');
    }

    public function show(Permission $permission): Response
    {
        Gate::authorize('view', $permission);

        return Inertia::render('Admin/Permissions/Show', [
            'permission' => $permission,
        ]);
    }

    public function edit(Permission $permission): Response
    {
        Gate::authorize('update', $permission);

        return Inertia::render('Admin/Permissions/Edit', [
            'permission' => $permission,
        ]);
    }

    public function update(UpdatePermissionRequest $request, Permission $permission, UpdatePermissionAction $action): RedirectResponse
    {
        Gate::authorize('update', $permission);

        $action->update($request, $permission);

        return redirect()
            ->route('admin.permissions.index')
            ->with('message', 'Cập nhật quyền hạn thành công.');
    }

    public function destroy(Permission $permission, DestroyPermissionAction $action): RedirectResponse
    {
        Gate::authorize('delete', $permission);

        $deleted = $action->destroy($permission);

        if (! $deleted) {
            return redirect()
                ->route('admin.permissions.index')
                ->with('error', 'Không thể xoá quyền hạn đang sử dụng cho vai trò.');
        }

        return redirect()
            ->route('admin.permissions.index')
            ->with('message', 'Xoá quyền hạn thành công.');
    }
}
