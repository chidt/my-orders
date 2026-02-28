<?php

namespace App\Http\Controllers\Site;

use App\Actions\Location\DeleteLocation;
use App\Actions\Location\StoreLocation;
use App\Actions\Location\UpdateLocation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Location\StoreLocationRequest;
use App\Http\Requests\Location\UpdateLocationRequest;
use App\Models\Location;
use App\Models\Site;
use App\Models\Warehouse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LocationController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the locations for the warehouse.
     */
    public function index(Site $site, Warehouse $warehouse): Response
    {
        $this->authorize('viewAny', [Location::class, $warehouse]);

        $locations = $warehouse->locations()
            ->withStock()
            ->orderBy('is_default', 'desc')
            ->orderBy('code')
            ->paginate(15);

        return Inertia::render('site/warehouses/locations/Index', [
            'site' => $site->only(['id', 'name', 'slug']),
            'warehouse' => $warehouse->only(['id', 'code', 'name', 'address']),
            'locations' => $locations,
        ]);
    }

    /**
     * Show the form for creating a new location.
     */
    public function create(Site $site, Warehouse $warehouse): Response
    {
        $this->authorize('create', [Location::class, $warehouse]);

        return Inertia::render('site/warehouses/locations/Create', [
            'site' => $site->only(['id', 'name', 'slug']),
            'warehouse' => $warehouse->only(['id', 'code', 'name', 'address']),
            'suggestedCode' => Location::generateUniqueCode($warehouse),
        ]);
    }

    /**
     * Store a newly created location in storage.
     */
    public function store(StoreLocationRequest $request, Site $site, Warehouse $warehouse, StoreLocation $action): RedirectResponse
    {
        $this->authorize('create', [Location::class, $warehouse]);

        $location = $action->execute($request->validated(), $warehouse);

        return redirect()
            ->route('site.warehouses.locations.index', [$site, $warehouse])
            ->with('success', 'Vị trí đã được tạo thành công.');
    }

    /**
     * Display the specified location.
     */
    public function show(Site $site, Warehouse $warehouse, Location $location): Response
    {
        $this->authorize('view', $location);

        return Inertia::render('site/warehouses/locations/Show', [
            'site' => $site->only(['id', 'name', 'slug']),
            'warehouse' => $warehouse->only(['id', 'code', 'name', 'address']),
            'location' => $location,
        ]);
    }

    /**
     * Show the form for editing the specified location.
     */
    public function edit(Site $site, Warehouse $warehouse, Location $location): Response
    {
        $this->authorize('update', $location);

        return Inertia::render('site/warehouses/locations/Edit', [
            'site' => $site->only(['id', 'name', 'slug']),
            'warehouse' => $warehouse->only(['id', 'code', 'name', 'address']),
            'location' => $location->only(['id', 'code', 'name', 'is_default']),
        ]);
    }

    /**
     * Update the specified location in storage.
     */
    public function update(UpdateLocationRequest $request, Site $site, Warehouse $warehouse, Location $location, UpdateLocation $action): RedirectResponse
    {
        $this->authorize('update', $location);

        $action->execute($location, $request->validated());

        return redirect()
            ->route('site.warehouses.locations.index', [$site, $warehouse])
            ->with('success', 'Vị trí đã được cập nhật thành công.');
    }

    /**
     * Remove the specified location from storage.
     */
    public function destroy(Site $site, Warehouse $warehouse, Location $location, DeleteLocation $action): RedirectResponse
    {
        $this->authorize('delete', $location);

        try {
            $action->execute($location);

            return redirect()
                ->route('site.warehouses.locations.index', [$site, $warehouse])
                ->with('success', 'Vị trí đã được xóa thành công.');
        } catch (\Exception $e) {
            return redirect()
                ->route('site.warehouses.locations.index', [$site, $warehouse])
                ->with('error', $e->getMessage());
        }
    }
}
