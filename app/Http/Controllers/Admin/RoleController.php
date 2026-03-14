<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Role\DestroyRoleAction;
use App\Actions\Admin\Role\IndexRoleAction;
use App\Actions\Admin\Role\StoreRoleAction;
use App\Actions\Admin\Role\UpdateRoleAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(IndexRoleAction $action): Response
    {
        Gate::authorize('viewAny', Role::class);

        $roles = $action->index();

        return Inertia::render('Admin/Roles/Index', [
            'roles' => $roles,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        Gate::authorize('create', Role::class);

        $permissions = Permission::orderBy('name')->get();

        return Inertia::render('Admin/Roles/Create', [
            'permissions' => $permissions,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoleRequest $request, StoreRoleAction $action): RedirectResponse
    {
        Gate::authorize('create', Role::class);

        $action->store($request);

        return redirect()
            ->route('admin.roles.index')
            ->with('message', 'Tạo vai trò thành công.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role): Response
    {
        Gate::authorize('view', $role);

        $role->load(['permissions', 'users']);

        return Inertia::render('Admin/Roles/Show', [
            'role' => $role,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role): Response
    {
        Gate::authorize('update', $role);

        $permissions = Permission::orderBy('name')->get();
        $role->load('permissions');

        return Inertia::render('Admin/Roles/Edit', [
            'role' => $role,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoleRequest $request, Role $role, UpdateRoleAction $action): RedirectResponse
    {
        Gate::authorize('update', $role);

        $action->update($request, $role);

        return redirect()
            ->route('admin.roles.index')
            ->with('message', 'Cập nhật vai trò thành công.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role, DestroyRoleAction $action): RedirectResponse
    {
        Gate::authorize('delete', $role);

        $deleted = $action->destroy($role);

        if (!$deleted) {
            return redirect()
                ->route('admin.roles.index')
                ->with('error', 'Không thể xoá vai trò đang sử dụng cho người dùng.');
        }

        return redirect()
            ->route('admin.roles.index')
            ->with('message', 'Xoá vai trò thành công.');
    }
}
