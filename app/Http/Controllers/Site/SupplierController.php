<?php

namespace App\Http\Controllers\Site;

use App\Actions\Supplier\DeleteSupplier;
use App\Actions\Supplier\ListSuppliers;
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
    public function index(Request $request, Site $site, ListSuppliers $action): Response
    {
        Gate::authorize('viewAny', [Supplier::class, $site]);

        $filters = [
            'search' => (string) $request->query('search', ''),
            'sort_by' => (string) $request->query('sort_by', 'name'),
            'sort_direction' => (string) $request->query('sort_direction', 'asc'),
        ];

        $suppliers = $action->execute($site, $filters);

        return Inertia::render('site/suppliers/Index', [
            'site' => $site->only(['id', 'name', 'slug']),
            'suppliers' => $suppliers,
            'statistics' => [
                'total' => Supplier::query()->where('site_id', $site->id)->count(),
            ],
            'filters' => $filters,
        ]);
    }

    public function create(Site $site): Response
    {
        Gate::authorize('create', [Supplier::class, $site]);

        return Inertia::render('site/suppliers/Create', [
            'site' => $site->only(['id', 'name', 'slug']),
        ]);
    }

    public function store(StoreSupplierRequest $request, Site $site, StoreSupplier $action): RedirectResponse
    {
        Gate::authorize('create', [Supplier::class, $site]);

        $action->execute($request->validated(), $site);

        return redirect()
            ->route('site.suppliers.index', $site)
            ->with('success', 'Nhà cung cấp đã được tạo thành công.');
    }

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

    public function update(UpdateSupplierRequest $request, Site $site, Supplier $supplier, UpdateSupplier $action): RedirectResponse
    {
        Gate::authorize('update', $supplier);

        $action->execute($supplier, $request->validated());

        return redirect()
            ->route('site.suppliers.index', $site)
            ->with('success', 'Nhà cung cấp đã được cập nhật thành công.');
    }

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
