<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Permission\DestroyPermissionAction;
use App\Actions\Admin\Permission\IndexPermissionAction;
use App\Actions\Admin\Permission\ShowPermissionAction;
use App\Actions\Admin\Permission\StorePermissionAction;
use App\Actions\Admin\Permission\UpdatePermissionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePermissionRequest;
use App\Http\Requests\Admin\UpdatePermissionRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index(IndexPermissionAction $action): Response
    {
        $data = $action->handle();

        return Inertia::render('Admin/Permissions/Index', $data);
    }

    public function store(StorePermissionRequest $request, StorePermissionAction $action): RedirectResponse
    {
        $action->handle($request);

        return redirect()
            ->route('admin.permissions.index')
            ->with('message', 'Tạo quyền hạn thành công.');
    }

    public function show(Permission $permission, ShowPermissionAction $action): Response
    {
        $data = $action->handle($permission);

        return Inertia::render('Admin/Permissions/Show', $data);
    }

    public function update(UpdatePermissionRequest $request, Permission $permission, UpdatePermissionAction $action): RedirectResponse
    {
        $action->handle($request, $permission);

        return redirect()
            ->route('admin.permissions.index')
            ->with('message', 'Cập nhật quyền hạn thành công.');
    }

    public function destroy(Permission $permission, DestroyPermissionAction $action): RedirectResponse
    {
        $action->handle($permission);

        return redirect()
            ->route('admin.permissions.index')
            ->with('message', 'Xóa quyền hạn thành công.');
    }
}
