<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Permission\CreatePermissionAction;
use App\Actions\Admin\Permission\DestroyPermissionAction;
use App\Actions\Admin\Permission\EditPermissionAction;
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
        $data = $action();

        return Inertia::render('Admin/Permissions/Index', $data);
    }

    public function create(CreatePermissionAction $action): Response
    {
        return $action();
    }

    public function store(StorePermissionRequest $request, StorePermissionAction $action): RedirectResponse
    {
        return $action($request);
    }

    public function show(Permission $permission, ShowPermissionAction $action): Response
    {
        $data = $action($permission);

        return Inertia::render('Admin/Permissions/Show', $data);
    }

    public function edit(Permission $permission, EditPermissionAction $action): Response
    {
        return $action($permission);
    }

    public function update(UpdatePermissionRequest $request, Permission $permission, UpdatePermissionAction $action): RedirectResponse
    {
        return $action($request, $permission);
    }

    public function destroy(Permission $permission, DestroyPermissionAction $action): RedirectResponse
    {
        return $action($permission);
    }
}
