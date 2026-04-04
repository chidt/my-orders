<?php

namespace App\Http\Controllers\Site;

use App\Actions\ProductType\CreateProductType;
use App\Actions\ProductType\DeleteProductType;
use App\Actions\ProductType\ListProductTypes;
use App\Actions\ProductType\ReorderProductTypes;
use App\Actions\ProductType\UpdateProductType;
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
    public function index(Request $request, ListProductTypes $action): Response
    {
        Gate::authorize('viewAny', ProductType::class);

        $filters = [
            'search' => (string) $request->query('search', ''),
            'show_on_front' => (string) $request->query('show_on_front', ''),
            'sort_by' => (string) $request->query('sort_by', 'order'),
            'sort_direction' => (string) $request->query('sort_direction', 'asc'),
        ];

        $payload = $action->execute((int) auth()->user()->site_id, $filters);

        return Inertia::render('Products/ProductTypes/Index', [
            'site' => auth()->user()->site,
            'productTypes' => $payload['productTypes'],
            'filters' => $filters,
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
    public function store(StoreProductTypeRequest $request, CreateProductType $createProductType)
    {
        Gate::authorize('create', ProductType::class);

        try {
            $createProductType->handle($request->validated(), auth()->user()->site_id);

            return redirect()->route('product-types.index', ['site' => auth()->user()->site->slug])
                ->with('success', 'Loại sản phẩm đã được tạo thành công.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Có lỗi xảy ra: '.$e->getMessage());
        }
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
    public function update(UpdateProductTypeRequest $request, $site, $productType, UpdateProductType $updateProductType)
    {
        // Manually resolve ProductType if it comes as string/ID
        if (is_string($productType) || is_numeric($productType)) {
            $productType = ProductType::forSite(auth()->user()->site_id)
                ->findOrFail($productType);
        }

        Gate::authorize('update', $productType);

        try {
            $updateProductType->handle($productType, $request->validated());

            return redirect()->route('product-types.index', ['site' => auth()->user()->site->slug])
                ->with('success', 'Loại sản phẩm đã được cập nhật thành công.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Có lỗi xảy ra: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($site, $productType, DeleteProductType $deleteProductType)
    {
        // Manually resolve ProductType if it comes as string/ID
        if (is_string($productType) || is_numeric($productType)) {
            $productType = ProductType::forSite(auth()->user()->site_id)
                ->findOrFail($productType);
        }

        Gate::authorize('delete', $productType);

        try {
            $deleteProductType->handle($productType);

            return redirect()->route('product-types.index', ['site' => auth()->user()->site->slug])
                ->with('success', 'Loại sản phẩm đã được xóa thành công.');
        } catch (\Exception $e) {
            return redirect()->route('product-types.index', ['site' => auth()->user()->site->slug])
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Reorder product types via drag & drop.
     */
    public function reorder(Request $request, ReorderProductTypes $reorderProductTypes)
    {
        Gate::authorize('create', ProductType::class);

        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:product_types,id',
            'items.*.order' => 'required|integer|min:0',
        ]);

        try {
            $reorderProductTypes->handle((int) auth()->user()->site_id, $request->items);

            return response()->json(['message' => 'Thứ tự đã được cập nhật thành công.']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
