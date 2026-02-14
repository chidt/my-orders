<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Role\CreateRoleAction;
use App\Actions\Admin\Role\DestroyRoleAction;
use App\Actions\Admin\Role\EditRoleAction;
use App\Actions\Admin\Role\IndexRoleAction;
use App\Actions\Admin\Role\ShowRoleAction;
use App\Actions\Admin\Role\StoreRoleAction;
use App\Actions\Admin\Role\UpdateRoleAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(IndexRoleAction $action): Response
    {
        return $action();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(CreateRoleAction $action): Response
    {
        return $action();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoleRequest $request, StoreRoleAction $action): RedirectResponse
    {
        return $action($request);
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role, ShowRoleAction $action): Response
    {
        return $action($role);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role, EditRoleAction $action): Response
    {
        return $action($role);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoleRequest $request, Role $role, UpdateRoleAction $action): RedirectResponse
    {
        return $action($request, $role);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role, DestroyRoleAction $action): RedirectResponse
    {
        return $action($role);
    }
}
