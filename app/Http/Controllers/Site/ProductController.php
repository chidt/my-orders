<?php

namespace App\Http\Controllers\Site;

use App\Actions\Product\DestroyProduct;
use App\Actions\Product\StoreProduct;
use App\Actions\Product\UpdateProduct;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\SyncChildProductsRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Attribute;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductAttributeValue;
use App\Models\ProductItem;
use App\Models\ProductType;
use App\Models\Supplier;
use App\Models\Tag;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Product::class);

        $siteId = auth()->user()->site_id;
        $query = Product::query()
            ->where('site_id', $siteId)
            ->with([
                'category:id,name',
                'supplier:id,name',
                'unit:id,name',
                'productType:id,name,color',
                'media',
                'tags' => fn ($q) => $q->where('site_id', $siteId),
            ])
            ->withCount('productItems')
            ->latest('id');

        if ($request->filled('search')) {
            $search = trim((string) $request->string('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%')
                    ->orWhere('supplier_code', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        if ($request->filled('product_type_id')) {
            $query->where('product_type_id', $request->integer('product_type_id'));
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->integer('supplier_id'));
        }

        if ($request->filled('tag_id')) {
            $tagId = $request->integer('tag_id');
            $query->whereHas('tags', function ($q) use ($tagId) {
                $q->where('tags.id', $tagId);
            });
        }

        $products = $query->paginate(20)->withQueryString()
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

        $formOptions = $this->getProductFormOptions($siteId);

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
        $formOptions = $this->getProductFormOptions($siteId);

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
        $formOptions = $this->getProductFormOptions($siteId);

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
            // TODO: bật lại eager-load các relation dưới khi DB/model đã sẵn sàng.
            // 'productItems.purchaseRequestDetails',
            // 'productItems.shoppingCartItems',
            // 'productItems.warehouseOutDetails',
            // 'productItems.warehouseReceiptDetails',
            // 'productItems.warehouseInventory',
        ]);
        $productModel->loadCount('productItems');

        $mainMedia = $productModel->getFirstMedia('main_image');
        $mainImage = $mainMedia
            ? [
                'id' => $mainMedia->id,
                'url' => $mainMedia->getUrl(),
            ]
            : null;

        $slideImages = $productModel
            ->getMedia('product_slider_images')
            ->map(fn ($m) => [
                'id' => $m->id,
                'url' => $m->getUrl(),
            ])
            ->values()
            ->all();
        $slideIdToIndex = collect($slideImages)
            ->pluck('id')
            ->values()
            ->flip()
            ->map(fn ($index) => (int) $index)
            ->all();

        $productItemKeys = $productModel->productItems
            ->mapWithKeys(function ($item) {
                $codes = $item->productItemAttributeValues
                    ->map(function ($pivot) {
                        $pv = $pivot->productAttributeValue;
                        if (! $pv) {
                            return null;
                        }

                        $attrOrder = (int) ($pv->attribute?->order ?? 0);

                        return [
                            'order' => $attrOrder,
                            'code' => strtoupper(trim((string) $pv->code)),
                        ];
                    })
                    ->filter()
                    ->sortBy(fn ($row) => $row['order'])
                    ->pluck('code')
                    ->values();

                $key = $codes->count() > 0 ? $codes->implode('-') : '__default__';

                return [(int) $item->id => $key];
            })
            ->all();

        $variantUploadImages = $productModel
            ->productItems
            ->flatMap(function ($item) {
                $imageKey = 'item-'.(int) $item->id;

                return $item->getMedia('variant_images')->map(function ($m) use ($imageKey) {
                    return [
                        'id' => $m->id,
                        'url' => $m->getUrl(),
                        'key' => $imageKey,
                    ];
                });
            })
            ->values()
            ->all();

        $variantImages = $productModel->productItems
            ->map(function ($item) use ($slideIdToIndex, $mainMedia) {
                $key = 'item-'.(int) $item->id;

                return [
                    'key' => $key,
                    'media_id' => $item->media_id ? (int) $item->media_id : null,
                    'slide_index' => $item->media_id && array_key_exists((int) $item->media_id, $slideIdToIndex)
                        ? (int) $slideIdToIndex[(int) $item->media_id]
                        : null,
                    'use_main_image' => $item->media_id
                        ? ((int) $item->media_id === (int) ($mainMedia?->id ?? 0))
                        : true,
                ];
            })
            ->values()
            ->all();

        $childProducts = $productModel->productItems
            ->map(fn ($item) => [
                'id' => (int) $item->id,
                'key' => $productItemKeys[(int) $item->id] ?? '__default__',
                'image_key' => 'item-'.(int) $item->id,
                'can_delete' => $this->canDeleteChildProduct($item),
                'sku' => (string) $item->sku,
                'name' => (string) $item->name,
                'purchase_price' => (float) $item->purchase_price,
                'partner_price' => (float) ($item->partner_price ?? 0),
                'sale_price' => (float) $item->price,
            ])
            ->values()
            ->all();

        $lockedProductAttributeValueIds = $productModel->productItems
            ->flatMap(fn ($item) => $item->productItemAttributeValues)
            ->map(fn ($pivot) => (int) ($pivot->product_attribute_value_id ?? 0))
            ->filter(fn (int $productAttributeValueId) => $productAttributeValueId > 0)
            ->unique()
            ->values()
            ->all();

        return Inertia::render('Products/Edit', [
            'site' => auth()->user()->site,
            'product' => $productModel,
            'mainImage' => $mainImage,
            'slideImages' => $slideImages,
            'variantUploadImages' => $variantUploadImages,
            'variantImages' => $variantImages,
            'childProducts' => $childProducts,
            'lockedProductAttributeValueIds' => $lockedProductAttributeValueIds,
            'categories' => $formOptions['categories'],
            'suppliers' => $formOptions['suppliers'],
            'productTypes' => $formOptions['productTypes'],
            'units' => $formOptions['units'],
            'locations' => $formOptions['locations'],
            'tags' => $formOptions['tags'],
            'attributes' => $formOptions['attributes'],

            'productAttributes' => $productModel->productAttributeValues
                ->groupBy('attribute_id')
                ->map(function ($values, $attributeId) {
                    return [
                        'attribute_id' => (int) $attributeId,
                        'values' => $values
                            ->sortBy('order')
                            ->map(fn (ProductAttributeValue $v) => [
                                'id' => $v->id,
                                'code' => $v->code,
                                'value' => $v->value,
                                'order' => $v->order,
                                'addition_value' => $v->addition_value,
                                'partner_addition_value' => $v->partner_addition_value,
                                'purchase_addition_value' => $v->purchase_addition_value,
                            ])->values()->all(),
                    ];
                })->values()->all(),
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

        $isInUse = ! $this->canDeleteChildProduct($childProduct);

        if ($isInUse) {
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

    private function canDeleteChildProduct(ProductItem $childProduct): bool
    {
        $hasOrderDetails = $childProduct->relationLoaded('orderDetails')
            ? $childProduct->orderDetails->isNotEmpty()
            : $childProduct->orderDetails()->exists();

        // TODO: Bật lại các check liên quan khác sau khi DB/model hoàn thiện:
        // - purchaseRequestDetails
        // - shoppingCartItems
        // - warehouseOutDetails
        // - warehouseReceiptDetails
        // - warehouseInventory

        return ! $hasOrderDetails;
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

    /**
     * @return array{
     *     categories: \Illuminate\Support\Collection<int, array{id:int,name:string}>,
     *     suppliers: \Illuminate\Database\Eloquent\Collection<int, Supplier>,
     *     productTypes: \Illuminate\Database\Eloquent\Collection<int, ProductType>,
     *     units: \Illuminate\Database\Eloquent\Collection<int, Unit>,
     *     locations: \Illuminate\Support\Collection<int, array{id:int,name:string,is_default:bool}>,
     *     attributes: \Illuminate\Database\Eloquent\Collection<int, Attribute>,
     *     tags: \Illuminate\Database\Eloquent\Collection<int, Tag>
     * }
     */
    private function getProductFormOptions(int $siteId): array
    {
        $categories = Category::forSite($siteId)
            ->ordered()
            ->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => implode(' > ', $category->breadcrumb),
            ]);

        $suppliers = Supplier::query()
            ->where('site_id', $siteId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $productTypes = ProductType::forSite($siteId)
            ->ordered()
            ->get(['id', 'name']);

        $units = Unit::query()
            ->orderBy('name')
            ->get(['id', 'name', 'unit']);

        $warehouses = Warehouse::forSite($siteId)
            ->with(['locations' => fn ($q) => $q->orderBy('is_default', 'desc')->orderBy('name')])
            ->orderBy('name')
            ->get();

        $locations = $warehouses->flatMap(function (Warehouse $warehouse) {
            return $warehouse->locations->map(fn (Location $location) => [
                'id' => $location->id,
                'name' => $warehouse->name.' - '.$location->name,
                'is_default' => (bool) $location->is_default,
            ]);
        })->values();

        $attributes = Attribute::query()
            ->where('site_id', $siteId)
            ->orderBy('order')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'order']);

        $tags = Tag::forSite($siteId)
            ->ordered()
            ->get(['id', 'name']);

        return [
            'categories' => $categories,
            'suppliers' => $suppliers,
            'productTypes' => $productTypes,
            'units' => $units,
            'locations' => $locations,
            'attributes' => $attributes,
            'tags' => $tags,
        ];
    }
}
