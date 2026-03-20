<?php

namespace App\Http\Controllers\Site;

use App\Actions\Supplier\DeleteSupplier;
use App\Actions\Supplier\StoreSupplier;
use App\Actions\Supplier\UpdateSupplier;
use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\StoreSupplierRequest;
use App\Http\Requests\Supplier\UpdateSupplierRequest;
use App\Models\Site;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    /**
     * Display a listing of the suppliers for the site.
     */
    public function index(Request $request, Site $site): Response
    {
        Gate::authorize('viewAny', [Supplier::class, $site]);

        $search = (string) $request->query('search', '');
        $sortBy = (string) $request->query('sort_by', 'name');
        $sortDirection = (string) $request->query('sort_direction', 'asc');

        $query = Supplier::query()
            ->where('site_id', $site->id)
            ->withCount('products');

        if ($search !== '') {
            $query->where(function ($subQuery) use ($search): void {
                $subQuery
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%')
                    ->orWhere('person_in_charge', 'like', '%'.$search.'%');
            });
        }

        if (in_array($sortBy, ['name', 'products_count', 'created_at'], true)) {
            if ($sortBy === 'products_count') {
                $query->orderBy('products_count', $sortDirection === 'desc' ? 'desc' : 'asc');
            } else {
                $query->orderBy($sortBy, $sortDirection === 'desc' ? 'desc' : 'asc');
            }
        } else {
            $query->orderBy('name');
        }

        $suppliers = $query->paginate(20)->withQueryString();

        $statistics = [
            'total' => Supplier::query()->where('site_id', $site->id)->count(),
        ];

        return Inertia::render('site/suppliers/Index', [
            'site' => $site->only(['id', 'name', 'slug']),
            'suppliers' => $suppliers,
            'statistics' => $statistics,
            'filters' => [
                'search' => $search,
                'sort_by' => $sortBy,
                'sort_direction' => $sortDirection,
            ],
        ]);
    }

    /**
     * Show the form for creating a new supplier.
     */
    public function create(Site $site): Response
    {
        Gate::authorize('create', [Supplier::class, $site]);

        return Inertia::render('site/suppliers/Create', [
            'site' => $site->only(['id', 'name', 'slug']),
        ]);
    }

    /**
     * Store a newly created supplier in storage.
     */
    public function store(StoreSupplierRequest $request, Site $site, StoreSupplier $action): RedirectResponse
    {
        Gate::authorize('create', [Supplier::class, $site]);

        $action->execute($request->validated(), $site);

        return redirect()
            ->route('site.suppliers.index', $site)
            ->with('success', 'Nhà cung cấp đã được tạo thành công.');
    }

    /**
     * Show the form for editing the specified supplier.
     */
    public function edit(Site $site, Supplier $supplier): Response
    {
        Gate::authorize('update', $supplier);

        return Inertia::render('site/suppliers/Edit', [
            'site' => $site->only(['id', 'name', 'slug']),
            'supplier' => $supplier->only([
                'id',
                'name',
                'person_in_charge',
                'phone',
                'address',
                'description',
            ]),
        ]);
    }

    /**
     * Update the specified supplier in storage.
     */
    public function update(UpdateSupplierRequest $request, Site $site, Supplier $supplier, UpdateSupplier $action): RedirectResponse
    {
        Gate::authorize('update', $supplier);

        $action->execute($supplier, $request->validated());

        return redirect()
            ->route('site.suppliers.index', $site)
            ->with('success', 'Nhà cung cấp đã được cập nhật thành công.');
    }

    /**
     * Remove the specified supplier from storage.
     */
    public function destroy(Site $site, Supplier $supplier, DeleteSupplier $action): RedirectResponse
    {
        Gate::authorize('delete', $supplier);

        try {
            $action->execute($supplier);

            return redirect()
                ->route('site.suppliers.index', $site)
                ->with('success', 'Nhà cung cấp đã được xóa thành công.');
        } catch (\Throwable $e) {
            return redirect()
                ->route('site.suppliers.index', $site)
                ->with('error', $e->getMessage());
        }
    }
}
