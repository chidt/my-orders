<?php

namespace App\Actions\Product;

use App\Models\Attribute;
use App\Models\Product;
use App\Models\ProductAttributeValue;
use App\Models\ProductItem;
use App\Models\ProductItemAttributeValue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\ImageOptimizer\OptimizerChainFactory;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class UpdateProduct
{
    public function handle(Product $product, array $validated, Request $request, int $siteId): void
    {
        DB::transaction(function () use ($product, $validated, $request, $siteId) {
            $newCode = $validated['code'] ?: $product->code;

            $product->update([
                'name' => $validated['name'],
                'code' => $newCode,
                'supplier_code' => $validated['supplier_code'] ?? null,
                'product_type_id' => $validated['product_type_id'],
                'description' => $validated['description'] ?? null,
                'qty_in_stock' => (int) ($validated['qty_in_stock'] ?? 0),
                'weight' => $validated['weight'] ?? null,
                'price' => $validated['price'],
                'partner_price' => $validated['partner_price'] ?? null,
                'purchase_price' => $validated['purchase_price'],
                'supplier_id' => $validated['supplier_id'],
                'unit_id' => $validated['unit_id'],
                'category_id' => $validated['category_id'],
                'order_closing_date' => $validated['order_closing_date'] ?? null,
                'default_location_id' => $validated['default_location_id'],
                'extra_attributes' => $validated['extra_attributes'] ?? null,
            ]);

            $product->tags()->sync($validated['tags'] ?? []);

            // Sync media first to resolve variant image references.
            $this->syncProductMedia($product, $request);
            $variantMedia = $this->resolveVariantMediaMap($product, $request);
            $variantExistingMediaMap = $variantMedia['existing'] ?? [];
            $variantUploadsByKey = $variantMedia['uploads'] ?? [];
            $variantSourceMap = $variantMedia['sources'] ?? [];
            $this->preserveExistingVariantUploadMedia($product, $variantExistingMediaMap, $variantSourceMap);

            $attributesInput = $validated['attributes'] ?? null;

            if (! is_array($attributesInput) || count($attributesInput) === 0) {
                $product->productItems()->delete();
                $product->productAttributeValues()->delete();

                $sku = $this->generateUniqueSku($newCode);

                $item = ProductItem::create([
                    'name' => $product->name,
                    'sku' => $sku,
                    'is_parent_image' => ($variantSourceMap['__default__'] ?? 'main') === 'main',
                    'is_parent_slider_image' => ($variantSourceMap['__default__'] ?? null) === 'slide',
                    'qty_in_stock' => $product->qty_in_stock,
                    'price' => $product->price,
                    'partner_price' => $product->partner_price,
                    'purchase_price' => $product->purchase_price,
                    'media_id' => $variantExistingMediaMap['__default__'] ?? null,
                    'product_id' => $product->id,
                    'site_id' => $siteId,
                ]);

                // If user uploaded an image for the default variant, store it on the ProductItem itself.
                if (array_key_exists('__default__', $variantUploadsByKey) && $variantUploadsByKey['__default__']) {
                    $uploadedMedia = $item
                        ->addMedia($variantUploadsByKey['__default__'])
                        ->toMediaCollection('variant_images');
                    $this->optimizeStoredMedia($uploadedMedia);
                    $item->update(['media_id' => (int) $uploadedMedia->id]);
                } elseif (($variantSourceMap['__default__'] ?? null) === 'custom') {
                    $this->attachPreservedVariantMediaToItem($product, $item, $variantExistingMediaMap['__default__'] ?? null);
                }

                return;
            }

            $product->productItems()->delete();
            $product->productAttributeValues()->delete();

            $selectedAttributeIds = collect($attributesInput)
                ->pluck('attribute_id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            /** @var Collection<int, Attribute> $selectedAttributes */
            $selectedAttributes = Attribute::query()
                ->where('site_id', $siteId)
                ->whereIn('id', $selectedAttributeIds)
                ->orderBy('order')
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'order'])
                ->keyBy('id');

            $valuesByAttributeId = [];

            foreach ($attributesInput as $attributeBlock) {
                $attributeId = (int) $attributeBlock['attribute_id'];
                $values = $attributeBlock['values'] ?? [];

                $createdValues = collect($values)
                    ->sortBy(fn ($v) => (int) ($v['order'] ?? 0))
                    ->map(function ($value) use ($product, $attributeId) {
                        return ProductAttributeValue::create([
                            'code' => strtoupper(trim((string) $value['code'])),
                            'value' => (string) $value['value'],
                            'order' => (int) $value['order'],
                            'purchase_addition_value' => $value['purchase_addition_value'] ?? null,
                            'partner_addition_value' => $value['partner_addition_value'] ?? null,
                            'addition_value' => $value['addition_value'] ?? null,
                            'product_id' => $product->id,
                            'attribute_id' => $attributeId,
                        ]);
                    })
                    ->values();

                $valuesByAttributeId[$attributeId] = $createdValues;
            }

            $attributeOrder = collect($selectedAttributeIds)
                ->sortBy(function ($id) use ($selectedAttributes) {
                    $attr = $selectedAttributes[$id] ?? null;

                    return [
                        $attr?->order ?? 0,
                        $attr?->name ?? '',
                        $id,
                    ];
                })
                ->values()
                ->all();

            $combinations = $this->cartesianProduct(array_map(
                fn ($attributeId) => $valuesByAttributeId[$attributeId]->all(),
                $attributeOrder,
            ));

            foreach ($combinations as $combination) {
                $skuBase = $newCode.'-'.collect($combination)->pluck('code')->implode('-');
                $sku = $this->generateUniqueSku($skuBase);
                $variantKey = collect($combination)
                    ->map(fn (ProductAttributeValue $v) => strtoupper(trim($v->code)))
                    ->implode('-');

                $addition = collect($combination)->sum(fn (ProductAttributeValue $v) => (float) ($v->addition_value ?? 0));
                $partnerAddition = collect($combination)->sum(fn (ProductAttributeValue $v) => (float) ($v->partner_addition_value ?? 0));
                $purchaseAddition = collect($combination)->sum(fn (ProductAttributeValue $v) => (float) ($v->purchase_addition_value ?? 0));

                $item = ProductItem::create([
                    'name' => $product->name.' - '.collect($combination)->pluck('value')->implode(' / '),
                    'sku' => $sku,
                    'is_parent_image' => ($variantSourceMap[$variantKey] ?? 'main') === 'main',
                    'is_parent_slider_image' => ($variantSourceMap[$variantKey] ?? null) === 'slide',
                    'qty_in_stock' => 0,
                    'price' => ((float) $product->price) + $addition,
                    'partner_price' => $product->partner_price === null
                        ? null
                        : ((float) $product->partner_price) + $partnerAddition,
                    'purchase_price' => ((float) $product->purchase_price) + $purchaseAddition,
                    'media_id' => $variantExistingMediaMap[$variantKey] ?? null,
                    'product_id' => $product->id,
                    'site_id' => $siteId,
                ]);

                foreach ($combination as $value) {
                    ProductItemAttributeValue::create([
                        'product_item_id' => $item->id,
                        'product_attribute_value_id' => $value->id,
                    ]);
                }

                // If user uploaded an image for this variant, store it on the ProductItem itself.
                if (array_key_exists($variantKey, $variantUploadsByKey) && $variantUploadsByKey[$variantKey]) {
                    $uploadedMedia = $item
                        ->addMedia($variantUploadsByKey[$variantKey])
                        ->toMediaCollection('variant_images');
                    $this->optimizeStoredMedia($uploadedMedia);
                    $item->update(['media_id' => (int) $uploadedMedia->id]);
                } elseif (($variantSourceMap[$variantKey] ?? null) === 'custom') {
                    $this->attachPreservedVariantMediaToItem($product, $item, $variantExistingMediaMap[$variantKey] ?? null);
                }
            }
        });
    }

    /**
     * Preserve existing custom variant media by moving it to Product temporarily.
     *
     * @param  array<string, int>  $variantExistingMediaMap
     * @param  array<string, string>  $variantSourceMap
     */
    private function preserveExistingVariantUploadMedia(
        Product $product,
        array $variantExistingMediaMap,
        array $variantSourceMap
    ): void {
        foreach ($variantExistingMediaMap as $key => $mediaId) {
            if (($variantSourceMap[$key] ?? null) !== 'custom') {
                continue;
            }

            $media = Media::query()->find((int) $mediaId);
            if (! $media) {
                continue;
            }

            if ($media->model_type !== ProductItem::class) {
                continue;
            }

            $media->model_type = Product::class;
            $media->model_id = $product->id;
            $media->collection_name = 'variant_images';
            $media->save();
        }
    }

    private function attachPreservedVariantMediaToItem(
        Product $product,
        ProductItem $item,
        int|null $mediaId
    ): void {
        if (! $mediaId) {
            return;
        }

        $media = Media::query()
            ->where('id', (int) $mediaId)
            ->where('model_type', Product::class)
            ->where('model_id', $product->id)
            ->first();

        if (! $media) {
            return;
        }

        $media->model_type = ProductItem::class;
        $media->model_id = $item->id;
        $media->collection_name = 'variant_images';
        $media->save();

        $item->update(['media_id' => (int) $media->id]);
    }

    private function generateUniqueSku(string $baseSku): string
    {
        $sku = $baseSku;
        $suffix = 1;

        while (ProductItem::query()->where('sku', $sku)->exists()) {
            $sku = $baseSku.'-'.$suffix;
            $suffix++;
        }

        return $sku;
    }

    /**
     * @param  array<int, array<int, ProductAttributeValue>>  $sets
     * @return array<int, array<int, ProductAttributeValue>>
     */
    private function cartesianProduct(array $sets): array
    {
        $result = [[]];

        foreach ($sets as $set) {
            $next = [];
            foreach ($result as $prefix) {
                foreach ($set as $item) {
                    $next[] = array_merge($prefix, [$item]);
                }
            }
            $result = $next;
        }

        return $result;
    }

    private function syncProductMedia(Product $product, Request $request): void
    {
        if ((bool) $request->boolean('remove_main_image')) {
            $product->clearMediaCollection('main_image');
            $product->update(['media_id' => null]);
        }

        $removeSlideMediaIds = $request->input('remove_slide_media_ids');
        if (is_array($removeSlideMediaIds) && count($removeSlideMediaIds) > 0) {
            $product
                ->getMedia('product_slider_images')
                ->whereIn('id', array_map('intval', $removeSlideMediaIds))
                ->each
                ->delete();
        }

        if ($request->hasFile('main_image')) {
            $product->clearMediaCollection('main_image');

            $media = $product
                ->addMediaFromRequest('main_image')
                ->toMediaCollection('main_image');
            $this->optimizeStoredMedia($media);

            $product->update(['media_id' => $media->id]);
        }

        if ($request->hasFile('slide_images')) {
            // Append new slide images (do not clear existing ones).
            // Existing images will only be removed if their media IDs are included in
            // `remove_slide_media_ids`.
            $product
                ->addMultipleMediaFromRequest(['slide_images'])
                ->each(function ($fileAdder) {
                    $media = $fileAdder
                        ->toMediaCollection('product_slider_images');
                    $this->optimizeStoredMedia($media);
                });

            // Enforce max 10 images in `product_slider_images`.
            $media = $product
                ->getMedia('product_slider_images')
                ->sortBy('order_column')
                ->values();

            if ($media->count() > 10) {
                $media
                    ->slice(10)
                    ->each(function ($m) {
                        $m->delete();
                    });
            }
        }
    }

    /**
     * @return array{existing: array<string,int>, uploads: array<string,mixed>, sources: array<string,string>}
     */
    private function resolveVariantMediaMap(Product $product, Request $request): array
    {
        $variantImages = $request->input('variant_images', []);
        $variantImageFiles = $request->file('variant_image_files', []);
        $variantImageFileKeys = $request->input('variant_image_file_keys', []);

        $hasVariantImages = is_array($variantImages) && count($variantImages) > 0;
        $hasVariantUploads = is_array($variantImageFiles) && count($variantImageFiles) > 0;

        if (! $hasVariantImages && ! $hasVariantUploads) {
            return ['existing' => [], 'uploads' => [], 'sources' => []];
        }

        $slideMediaIds = $product
            ->getMedia('product_slider_images')
            ->sortBy('order_column')
            ->pluck('id')
            ->values()
            ->all();
        $mainMediaId = (int) ($product->getFirstMedia('main_image')?->id ?? 0);

        $allowedMediaIds = $product
            ->getMedia('product_slider_images')
            ->pluck('id')
            ->merge($product->getMedia('main_image')->pluck('id'))
            // Backward-compat: older versions may have stored variant images on Product.
            ->merge($product->getMedia('variant_images')->pluck('id'))
            ->all();

        // For the current version, variant images are stored on ProductItem: collection `variant_images`.
        $productItemVariantMediaIds = $product
            ->productItems()
            ->get()
            ->flatMap(fn ($item) => $item->getMedia('variant_images')->pluck('id'))
            ->all();

        $allowedMediaIds = array_values(array_unique(array_merge(
            array_map(fn ($id) => (int) $id, $allowedMediaIds),
            array_map(fn ($id) => (int) $id, $productItemVariantMediaIds),
        )));

        $existing = [];
        $sources = [];
        foreach ($variantImages as $row) {
            if (! is_array($row)) {
                continue;
            }

            $key = strtoupper(trim((string) ($row['key'] ?? '')));
            if ($key === '') {
                continue;
            }

            if ((bool) ($row['use_main_image'] ?? false)) {
                if ($mainMediaId > 0) {
                    $existing[$key] = $mainMediaId;
                }
                $sources[$key] = 'main';
                continue;
            }

            $mediaId = null;
            if (isset($row['media_id']) && $row['media_id'] !== null) {
                $candidate = (int) $row['media_id'];
                if (in_array($candidate, $allowedMediaIds, true)) {
                    $mediaId = $candidate;
                }
            }

            if ($mediaId === null && isset($row['slide_index']) && $row['slide_index'] !== null) {
                $slideIndex = (int) $row['slide_index'];
                if (array_key_exists($slideIndex, $slideMediaIds)) {
                    $mediaId = (int) $slideMediaIds[$slideIndex];
                }
            }

            if ($mediaId !== null) {
                $existing[$key] = $mediaId;
                $sources[$key] = $mediaId === $mainMediaId
                    ? 'main'
                    : (in_array($mediaId, $slideMediaIds, true) ? 'slide' : 'custom');
            }
        }

        $uploads = [];
        if (is_array($variantImageFiles) && is_array($variantImageFileKeys)) {
            foreach ($variantImageFiles as $index => $uploadedFile) {
                $key = strtoupper(trim((string) ($variantImageFileKeys[$index] ?? '')));
                if ($key === '' || ! $uploadedFile) {
                    continue;
                }

                $uploads[$key] = $uploadedFile;
                $sources[$key] = 'upload';
            }
        }

        return ['existing' => $existing, 'uploads' => $uploads, 'sources' => $sources];
    }

    private function optimizeStoredMedia(Media $media): void
    {
        try {
            if (! str_starts_with((string) $media->mime_type, 'image/')) {
                return;
            }

            OptimizerChainFactory::create()->optimize($media->getPath());
        } catch (\Throwable) {
            // Ignore optimization failures; keep upload flow stable.
        }
    }
}

