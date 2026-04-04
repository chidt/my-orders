<?php

namespace App\Http\Controllers\Site;

use App\Actions\Product\BuildProductEditPayload;
use App\Actions\Product\BuildProductFormOptions;
use App\Actions\Product\DestroyProduct;
use App\Actions\Product\ListProducts;
use App\Actions\Product\SearchProductItems;
use App\Actions\Product\StoreProduct;
use App\Actions\Product\UpdateProduct;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\SyncChildProductsRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Product;
use App\Models\ProductItem;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request, ListProducts $action): Response
    {
        Gate::authorize('viewAny', Product::class);

        $siteId = auth()->user()->site_id;
        $products = $action->execute($siteId, $request->query())
            ->through(function (Product $product) {
                $mainImage = $product->getFirstMedia('main_image');

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'code' => $product->code,
                    'supplier_code' => $product->supplier_code,
                    'price' => $product->price,
                    'partner_price' => $product->partner_price,
                    'purchase_price' => $product->purchase_price,
                    'qty_in_stock' => $product->qty_in_stock,
                    'product_items_count' => $product->product_items_count,
                    'thumbnail_url' => $mainImage?->getUrl(),
                    'category' => $product->category
                        ? ['id' => $product->category->id, 'name' => $product->category->name]
                        : null,
                    'supplier' => $product->supplier
                        ? ['id' => $product->supplier->id, 'name' => $product->supplier->name]
                        : null,
                    'unit' => $product->unit
                        ? ['id' => $product->unit->id, 'name' => $product->unit->name]
                        : null,
                    'product_type' => $product->productType
                        ? ['id' => $product->productType->id,
                            'name' => $product->productType->name,
                            'color' => $product->productType->color]
                        : null,
                ];
            });

        $formOptions = app(BuildProductFormOptions::class)->execute($siteId);

        return Inertia::render('Products/Index', [
            'site' => auth()->user()->site,
            'products' => $products,
            'filters' => $request->only(['search', 'category_id', 'product_type_id', 'supplier_id', 'tag_id']),
            'categories' => $formOptions['categories'],
            'suppliers' => $formOptions['suppliers'],
            'productTypes' => $formOptions['productTypes'],
            'tags' => $formOptions['tags'],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Product::class);

        $siteId = auth()->user()->site_id;
        $formOptions = app(BuildProductFormOptions::class)->execute($siteId);

        return Inertia::render('Products/Create', [
            'site' => auth()->user()->site,
            'categories' => $formOptions['categories'],
            'suppliers' => $formOptions['suppliers'],
            'productTypes' => $formOptions['productTypes'],
            'units' => $formOptions['units'],
            'locations' => $formOptions['locations'],
            'attributes' => $formOptions['attributes'],
            'tags' => $formOptions['tags'],
        ]);
    }

    public function edit($site, $product): Response
    {
        $productModel = $this->findProductForSite((int) $product);
        Gate::authorize('update', $productModel);

        $siteId = auth()->user()->site_id;
        $formOptions = app(BuildProductFormOptions::class)->execute($siteId);

        $productModel->load([
            'category',
            'supplier',
            'unit',
            'productType',
            'defaultLocation',
            'tags' => fn ($q) => $q->where('site_id', $siteId),
            'productAttributeValues.attribute',
            'productItems.productItemAttributeValues.productAttributeValue.attribute',
            'productItems.orderDetails',
        ]);
        $productModel->loadCount('productItems');

        $editPayload = app(BuildProductEditPayload::class)->execute($productModel);

        return Inertia::render('Products/Edit', [
            'site' => auth()->user()->site,
            'product' => $productModel,
            ...$editPayload,
            'categories' => $formOptions['categories'],
            'suppliers' => $formOptions['suppliers'],
            'productTypes' => $formOptions['productTypes'],
            'units' => $formOptions['units'],
            'locations' => $formOptions['locations'],
            'tags' => $formOptions['tags'],
            'attributes' => $formOptions['attributes'],
        ]);
    }

    public function update(UpdateProductRequest $request, $site, $product, UpdateProduct $updateProduct)
    {
        $productModel = $this->findProductForSite((int) $product);
        Gate::authorize('update', $productModel);

        try {
            $updateProduct->handle(
                $productModel,
                $request->validated(),
                $request,
                (int) auth()->user()->site_id,
            );
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return back()->withInput()->with('error', 'Có lỗi xảy ra: '.$e->getMessage());
        }

        return redirect()->route('products.edit', [
            'site' => auth()->user()->site->slug,
            'product' => $productModel->id,
        ])
            ->with('success', 'Sản phẩm đã được cập nhật thành công.');
    }

    public function syncChildProducts(SyncChildProductsRequest $request, $site, $product, UpdateProduct $updateProduct)
    {
        $productModel = $this->findProductForSite((int) $product);
        Gate::authorize('update', $productModel);

        try {
            $updateProduct->syncChildProductsOnly(
                $productModel,
                (int) auth()->user()->site_id,
                $request,
            );
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return back()->with('error', 'Không thể đồng bộ sản phẩm con: '.$e->getMessage());
        }

        return back()->with('success', 'Đã đồng bộ sản phẩm con thành công.');
    }

    public function destroyChildProduct($site, $product, $productItem)
    {
        $productModel = $this->findProductForSite((int) $product);
        Gate::authorize('update', $productModel);

        $childProduct = ProductItem::query()
            ->where('id', (int) $productItem)
            ->where('product_id', $productModel->id)
            ->first();

        if (! $childProduct) {
            abort(404);
        }

        $hasOrderDetails = $childProduct->relationLoaded('orderDetails')
            ? $childProduct->orderDetails->isNotEmpty()
            : $childProduct->orderDetails()->exists();

        if ($hasOrderDetails) {
            return back()->with('error', 'Không thể xóa sản phẩm con đã phát sinh dữ liệu.');
        }

        try {
            $childProduct->productItemAttributeValues()->delete();
            $childProduct->clearMediaCollection('variant_images');
            $childProduct->delete();
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return back()->with('error', 'Không thể xóa sản phẩm con: '.$e->getMessage());
        }

        return back()->with('success', 'Đã xóa sản phẩm con thành công.');
    }

    public function destroy($site, $product, DestroyProduct $destroyProduct)
    {
        $productModel = $this->findProductForSite((int) $product);
        Gate::authorize('delete', $productModel);

        try {
            $destroyProduct->handle($productModel);
        } catch (\Exception $e) {
            return back()->with('error', 'Không thể xóa sản phẩm: '.$e->getMessage());
        }

        return redirect()->route('products.index', ['site' => auth()->user()->site->slug])
            ->with('success', 'Sản phẩm đã được xóa thành công.');
    }

    private function findProductForSite(int $id): Product
    {
        $product = Product::query()
            ->where('site_id', auth()->user()->site_id)
            ->find($id);

        if (! $product) {
            abort(404);
        }

        return $product;
    }

    public function store(StoreProductRequest $request, StoreProduct $storeProduct)
    {
        Gate::authorize('create', Product::class);

        $site = auth()->user()->site;
        try {
            $storeProduct->handle(
                $request->validated(),
                $request,
                (int) auth()->user()->site_id,
                is_array($site->settings) ? $site->settings : [],
            );
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Có lỗi xảy ra: '.$e->getMessage());
        }

        return redirect()->route('products.index', ['site' => $site->slug])
            ->with('success', 'Sản phẩm đã được tạo thành công.');
    }

    public function searchItems(Site $site, Request $request, SearchProductItems $action): JsonResponse
    {
        Gate::authorize('viewAny', Product::class);

        $items = $action->execute($site, $request);

        return response()->json([
            'data' => $items->map(fn (ProductItem $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'sku' => $item->sku,
                'price' => (float) $item->price,
                'product_name' => $item->product?->name,
                'image' => $item->image,
            ])->values()->all(),
        ]);
    }
}
