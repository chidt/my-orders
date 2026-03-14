<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductType\StoreProductTypeRequest;
use App\Http\Requests\ProductType\UpdateProductTypeRequest;
use App\Models\ProductType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ProductTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', ProductType::class);

        $query = ProductType::forSite(auth()->user()->site_id)
            ->withCount('products')
            ->ordered();

        // Search functionality
        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        // Filter by show_on_front
        if ($request->filled('show_on_front')) {
            $query->where('show_on_front', $request->boolean('show_on_front'));
        }

        // Sort functionality
        $sortBy = $request->get('sort_by', 'order');
        $sortDirection = $request->get('sort_direction', 'asc');

        if (in_array($sortBy, ['name', 'order', 'products_count'])) {
            if ($sortBy === 'products_count') {
                $query->orderBy('products_count', $sortDirection);
            } else {
                $query->orderBy($sortBy, $sortDirection);
            }
        }

        $productTypes = $query->paginate(20)->withQueryString();

        return Inertia::render('Products/ProductTypes/Index', [
            'site' => auth()->user()->site,
            'productTypes' => $productTypes,
            'filters' => $request->only(['search', 'show_on_front', 'sort_by', 'sort_direction']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        Gate::authorize('create', ProductType::class);

        return Inertia::render('Products/ProductTypes/Create', [
            'site' => auth()->user()->site,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductTypeRequest $request)
    {
        Gate::authorize('create', ProductType::class);

        $validated = $request->validated();
        $validated['site_id'] = auth()->user()->site_id;

        $productType = ProductType::create($validated);

        return redirect()->route('product-types.index', ['site' => auth()->user()->site->slug])
            ->with('success', 'Loại sản phẩm đã được tạo thành công.');
    }

    /**
     * Display the specified resource.
     */
    public function show($site, $productType): Response
    {
        // Manually resolve ProductType if it comes as string/ID
        if (is_string($productType) || is_numeric($productType)) {
            $productType = ProductType::forSite(auth()->user()->site_id)
                ->findOrFail($productType);
        }

        Gate::authorize('view', $productType);

        $productType->load('site')->loadCount('products');

        return Inertia::render('Products/ProductTypes/Show', [
            'site' => auth()->user()->site,
            'productType' => $productType,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($site, $productType): Response
    {
        // Manually resolve ProductType if it comes as string/ID
        if (is_string($productType) || is_numeric($productType)) {
            $productType = ProductType::forSite(auth()->user()->site_id)
                ->findOrFail($productType);
        }

        Gate::authorize('update', $productType);

        return Inertia::render('Products/ProductTypes/Edit', [
            'site' => auth()->user()->site,
            'productType' => $productType,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductTypeRequest $request, $site, $productType)
    {

        // Manually resolve ProductType if it comes as string/ID
        if (is_string($productType) || is_numeric($productType)) {
            $productType = ProductType::forSite(auth()->user()->site_id)
                ->findOrFail($productType);
        }

        Gate::authorize('update', $productType);

        $productType->update($request->validated());

        return redirect()->route('product-types.index', ['site' => auth()->user()->site->slug])
            ->with('success', 'Loại sản phẩm đã được cập nhật thành công.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($site, $productType)
    {
        // Manually resolve ProductType if it comes as string/ID
        if (is_string($productType) || is_numeric($productType)) {
            $productType = ProductType::forSite(auth()->user()->site_id)
                ->findOrFail($productType);
        }

        Gate::authorize('delete', $productType);

        if (! $productType->canDelete()) {
            return redirect()->route('product-types.index', ['site' => auth()->user()->site->slug])
                ->with('error', 'Không thể xóa loại sản phẩm này vì có sản phẩm đang sử dụng.');
        }

        $productType->delete();

        return redirect()->route('product-types.index', ['site' => auth()->user()->site->slug])
            ->with('success', 'Loại sản phẩm đã được xóa thành công.');
    }

    /**
     * Reorder product types via drag & drop.
     */
    public function reorder(Request $request)
    {
        Gate::authorize('create', ProductType::class);

        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:product_types,id',
            'items.*.order' => 'required|integer|min:0',
        ]);

        foreach ($request->items as $item) {
            ProductType::where('id', $item['id'])
                ->where('site_id', auth()->user()->site_id)
                ->update(['order' => $item['order']]);
        }

        return response()->json(['message' => 'Thứ tự đã được cập nhật thành công.']);
    }
}
