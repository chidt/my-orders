<?php

namespace App\Http\Controllers\Site;

use App\Actions\Warehouse\DeleteWarehouse;
use App\Actions\Warehouse\StoreWarehouse;
use App\Actions\Warehouse\UpdateWarehouse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\StoreWarehouseRequest;
use App\Http\Requests\Warehouse\UpdateWarehouseRequest;
use App\Models\Site;
use App\Models\Warehouse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class WarehouseController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the warehouses for the site.
     */
    public function index(Site $site): Response
    {
        $this->authorize('viewAny', [Warehouse::class, $site]);

        $warehouses = $site->warehouses()
            ->withLocationsCount()
            ->orderBy('name')
            ->paginate(15);

        return Inertia::render('site/warehouses/Index', [
            'site' => $site->only(['id', 'name', 'slug']),
            'warehouses' => $warehouses,
        ]);
    }

    /**
     * Show the form for creating a new warehouse.
     */
    public function create(Site $site): Response
    {
        $this->authorize('create', [Warehouse::class, $site]);

        return Inertia::render('site/warehouses/Create', [
            'site' => $site->only(['id', 'name', 'slug']),
            'suggestedCode' => Warehouse::generateUniqueCode($site),
        ]);
    }

    /**
     * Store a newly created warehouse in storage.
     */
    public function store(StoreWarehouseRequest $request, Site $site, StoreWarehouse $action): RedirectResponse
    {
        $this->authorize('create', [Warehouse::class, $site]);

        $warehouse = $action->execute($request->validated(), $site);

        return redirect()
            ->route('site.warehouses.index', $site)
            ->with('success', 'Kho đã được tạo thành công.');
    }

    /**
     * Display the specified warehouse.
     */
    public function show(Site $site, Warehouse $warehouse): Response
    {
        $this->authorize('view', $warehouse);

        $warehouse->load(['locations' => function ($query) {
            $query->orderBy('name');
        }]);

        return Inertia::render('site/warehouses/Show', [
            'site' => $site->only(['id', 'name', 'slug']),
            'warehouse' => $warehouse,
        ]);
    }

    /**
     * Show the form for editing the specified warehouse.
     */
    public function edit(Site $site, Warehouse $warehouse): Response
    {
        $this->authorize('update', $warehouse);

        return Inertia::render('site/warehouses/Edit', [
            'site' => $site->only(['id', 'name', 'slug']),
            'warehouse' => $warehouse->only(['id', 'code', 'name', 'address']),
        ]);
    }

    /**
     * Update the specified warehouse in storage.
     */
    public function update(UpdateWarehouseRequest $request, Site $site, Warehouse $warehouse, UpdateWarehouse $action): RedirectResponse
    {
        $this->authorize('update', $warehouse);

        $action->execute($warehouse, $request->validated());

        return redirect()
            ->route('site.warehouses.index', $site)
            ->with('success', 'Kho đã được cập nhật thành công.');
    }

    /**
     * Remove the specified warehouse from storage.
     */
    public function destroy(Site $site, Warehouse $warehouse, DeleteWarehouse $action): RedirectResponse
    {
        $this->authorize('delete', $warehouse);

        try {
            $action->execute($warehouse);

            return redirect()
                ->route('site.warehouses.index', $site)
                ->with('success', 'Kho đã được xóa thành công.');
        } catch (\Exception $e) {
            return redirect()
                ->route('site.warehouses.index', $site)
                ->with('error', $e->getMessage());
        }
    }
}
