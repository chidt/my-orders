<?php

namespace App\Actions\Product;

use App\Models\Product;
use App\Models\ProductAttributeValue;
use App\Models\ProductItem;
use App\Models\ProductItemAttributeValue;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Spatie\ImageOptimizer\OptimizerChainFactory;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class UpdateProduct
{
    public function handle(Product $product, array $validated, Request $request, int $siteId): void
    {
        DB::transaction(function () use ($product, $validated, $request) {
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

            $this->syncProductMedia($product, $request);

            $attributesInput = $validated['attributes'] ?? [];
            if (! is_array($attributesInput)) {
                $attributesInput = [];
            }

            $keptAttributeValueIds = [];
            $existingAttributeValuesCollection = $product
                ->productAttributeValues()
                ->get();
            $existingAttributeValuesById = $existingAttributeValuesCollection->keyBy('id');
            $existingAttributeValuesByCode = $existingAttributeValuesCollection
                ->keyBy(fn (ProductAttributeValue $value) => $this->makeAttributeValueMapKey(
                    (int) $value->attribute_id,
                    (string) $value->code,
                ));

            foreach ($attributesInput as $attributeBlock) {
                $attributeId = (int) ($attributeBlock['attribute_id'] ?? 0);
                $values = $attributeBlock['values'] ?? [];
                if ($attributeId <= 0 || ! is_array($values)) {
                    continue;
                }

                collect($values)
                    ->sortBy(fn ($v) => (int) ($v['order'] ?? 0))
                    ->each(function ($value) use (
                        $product,
                        $attributeId,
                        $existingAttributeValuesById,
                        $existingAttributeValuesByCode,
                        &$keptAttributeValueIds
                    ) {
                        $payload = [
                            'code' => strtoupper(trim((string) ($value['code'] ?? ''))),
                            'value' => (string) ($value['value'] ?? ''),
                            'order' => (int) ($value['order'] ?? 0),
                            'purchase_addition_value' => $value['purchase_addition_value'] ?? null,
                            'partner_addition_value' => $value['partner_addition_value'] ?? null,
                            'addition_value' => $value['addition_value'] ?? null,
                            'product_id' => $product->id,
                            'attribute_id' => $attributeId,
                        ];

                        $existing = null;
                        $requestedId = (int) ($value['id'] ?? 0);
                        if ($requestedId > 0) {
                            $existing = $existingAttributeValuesById->get($requestedId);
                        }

                        if (! $existing) {
                            $mapKey = $this->makeAttributeValueMapKey($attributeId, $payload['code']);
                            $existing = $existingAttributeValuesByCode->get($mapKey);
                        }

                        if ($existing) {
                            $existing->update($payload);
                            $keptAttributeValueIds[] = (int) $existing->id;
                            $existingAttributeValuesById->put((int) $existing->id, $existing);
                            $existingAttributeValuesByCode->put(
                                $this->makeAttributeValueMapKey($attributeId, (string) $payload['code']),
                                $existing,
                            );

                            return;
                        }

                        $created = ProductAttributeValue::create($payload);
                        $keptAttributeValueIds[] = (int) $created->id;
                        $existingAttributeValuesById->put((int) $created->id, $created);
                        $existingAttributeValuesByCode->put(
                            $this->makeAttributeValueMapKey($attributeId, (string) $created->code),
                            $created,
                        );
                    });
            }

            $attributeValueIdsToDelete = $product
                ->productAttributeValues()
                ->whereNotIn('id', $keptAttributeValueIds)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if (count($attributeValueIdsToDelete) > 0) {
                ProductItemAttributeValue::query()
                    ->whereIn('product_attribute_value_id', $attributeValueIdsToDelete)
                    ->delete();

                $product
                    ->productAttributeValues()
                    ->whereIn('id', $attributeValueIdsToDelete)
                    ->delete();
            }

        });
    }

    public function syncChildProductsOnly(Product $product, int $siteId, Request $request): void
    {
        DB::transaction(function () use ($product, $siteId, $request) {
            $this->syncChildProducts($product, $siteId, (string) $product->code);
            $this->syncChildProductImages($product, $request);
        });
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

    private function resolveVariantPartnerPrice(mixed $productPartnerPrice, float $partnerAddition): ?float
    {
        if (blank($productPartnerPrice) && $partnerAddition == 0.0) {
            return null;
        }

        return (float) ($productPartnerPrice ?? 0) + $partnerAddition;
    }

    private function makeAttributeValueMapKey(int $attributeId, string $code): string
    {
        return $attributeId.'|'.strtoupper(trim($code));
    }

    private function syncChildProducts(Product $product, int $siteId, string $newCode): void
    {
        $product->load([
            'productAttributeValues.attribute',
            'productItems.productItemAttributeValues.productAttributeValue.attribute',
        ]);

        foreach ($product->productItems as $existingItem) {
            $this->updateItemPricesFromCurrentPivots($existingItem, $product);
        }

        $valuesByAttribute = $product->productAttributeValues
            ->groupBy('attribute_id')
            ->map(fn ($values) => $values->sortBy('order')->values())
            ->all();

        $attributeOrder = $product->productAttributeValues
            ->groupBy('attribute_id')
            ->sortBy(fn ($values) => (int) ($values->first()?->attribute?->order ?? 0))
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if (count($attributeOrder) === 0) {
            return;
        }

        $existingItemsByKey = $product->productItems
            ->mapWithKeys(function (ProductItem $item) {
                $key = $this->buildVariantKeyFromItemPivots($item);
                if ($key === '') {
                    return [];
                }

                return [$key => $item];
            })
            ->all();

        $combinations = $this->cartesianProduct(array_map(
            fn ($attributeId) => $valuesByAttribute[$attributeId]->all(),
            $attributeOrder
        ));

        foreach ($combinations as $combination) {
            $key = collect($combination)
                ->map(fn (ProductAttributeValue $value) => strtoupper(trim($value->code)))
                ->implode('-');

            $name = $product->name.' - '.collect($combination)->pluck('value')->implode(' - ');
            $skuBase = $newCode.'-'.$key;

            $addition = collect($combination)->sum(fn (ProductAttributeValue $v) => (float) ($v->addition_value ?? 0));
            $partnerAddition = collect($combination)->sum(fn (ProductAttributeValue $v) => (float) ($v->partner_addition_value ?? 0));
            $purchaseAddition = collect($combination)->sum(fn (ProductAttributeValue $v) => (float) ($v->purchase_addition_value ?? 0));

            $payload = [
                'name' => $name,
                'sku' => $this->generateUniqueSku($skuBase, $existingItemsByKey[$key]->id ?? null),
                'price' => ((float) $product->price) + $addition,
                'partner_price' => $this->resolveVariantPartnerPrice($product->partner_price, $partnerAddition),
                'purchase_price' => ((float) $product->purchase_price) + $purchaseAddition,
            ];

            $existingItem = $existingItemsByKey[$key] ?? null;
            if ($existingItem) {
                $existingItem->update($payload);
                $this->syncItemAttributeValues($existingItem, $combination);

                continue;
            }

            $item = ProductItem::create(array_merge($payload, [
                'qty_in_stock' => 0,
                'is_parent_image' => true,
                'is_parent_slider_image' => false,
                'media_id' => $product->media_id,
                'product_id' => $product->id,
                'site_id' => $siteId,
            ]));

            $this->syncItemAttributeValues($item, $combination);
        }
    }

    /**
     * @param  array<int, ProductAttributeValue>  $combination
     */
    private function syncItemAttributeValues(ProductItem $item, array $combination): void
    {
        $item->productItemAttributeValues()->delete();

        foreach ($combination as $value) {
            ProductItemAttributeValue::create([
                'product_item_id' => $item->id,
                'product_attribute_value_id' => $value->id,
            ]);
        }
    }

    private function updateItemPricesFromCurrentPivots(ProductItem $item, Product $product): void
    {
        $values = $item->productItemAttributeValues
            ->map(fn (ProductItemAttributeValue $pivot) => $pivot->productAttributeValue)
            ->filter();

        $addition = $values->sum(fn (ProductAttributeValue $v) => (float) ($v->addition_value ?? 0));
        $partnerAddition = $values->sum(fn (ProductAttributeValue $v) => (float) ($v->partner_addition_value ?? 0));
        $purchaseAddition = $values->sum(fn (ProductAttributeValue $v) => (float) ($v->purchase_addition_value ?? 0));

        $item->update([
            'price' => ((float) $product->price) + $addition,
            'partner_price' => $this->resolveVariantPartnerPrice($product->partner_price, $partnerAddition),
            'purchase_price' => ((float) $product->purchase_price) + $purchaseAddition,
        ]);
    }

    private function buildVariantKeyFromItemPivots(ProductItem $item): string
    {
        $codes = $item->productItemAttributeValues
            ->map(function (ProductItemAttributeValue $pivot) {
                $value = $pivot->productAttributeValue;
                if (! $value) {
                    return null;
                }

                return [
                    'order' => (int) ($value->attribute?->order ?? 0),
                    'code' => strtoupper(trim((string) $value->code)),
                ];
            })
            ->filter()
            ->sortBy('order')
            ->pluck('code')
            ->values();

        return $codes->implode('-');
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

    private function generateUniqueSku(string $baseSku, ?int $ignoreProductItemId = null): string
    {
        $sku = $baseSku;
        $suffix = 1;

        while (ProductItem::query()
            ->when($ignoreProductItemId !== null, fn ($query) => $query->where('id', '!=', $ignoreProductItemId))
            ->where('sku', $sku)
            ->exists()) {
            $sku = $baseSku.'-'.$suffix;
            $suffix++;
        }

        return $sku;
    }

    private function syncChildProductImages(Product $product, Request $request): void
    {
        $product->load([
            'productItems.productItemAttributeValues.productAttributeValue.attribute',
        ]);

        $itemsByImageKey = $product->productItems
            ->mapWithKeys(function (ProductItem $item) {
                $key = strtoupper('item-'.(int) $item->id);
                if ($key === '') {
                    return [];
                }

                return [$key => $item];
            })
            ->all();

        if (count($itemsByImageKey) === 0) {
            return;
        }

        $variantImageRows = $this->normalizeVariantImageRows($request->input('variant_images'));
        $uploadedFilesByKey = $this->mapUploadedVariantFilesByKey(
            $request->file('variant_image_files'),
            $request->input('variant_image_file_keys')
        );
        $slideMediaIds = $product
            ->getMedia('product_slider_images')
            ->sortBy('order_column')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
        $slideMediaIdSet = array_fill_keys($slideMediaIds, true);
        $mainMediaId = $product->media_id ? (int) $product->media_id : null;

        foreach ($itemsByImageKey as $key => $item) {
            if (array_key_exists($key, $uploadedFilesByKey)) {
                $item->clearMediaCollection('variant_images');
                $media = $item
                    ->addMedia($uploadedFilesByKey[$key])
                    ->toMediaCollection('variant_images');
                $this->optimizeStoredMedia($media);
                $item->update([
                    'media_id' => (int) $media->id,
                    'is_parent_image' => false,
                    'is_parent_slider_image' => false,
                ]);

                continue;
            }

            $row = $variantImageRows[$key] ?? null;
            if (! is_array($row)) {
                continue;
            }

            $selectedMediaId = null;
            if (($row['use_main_image'] ?? false) === true) {
                $selectedMediaId = $mainMediaId;
            } elseif (($row['media_id'] ?? null) !== null) {
                $selectedMediaId = (int) $row['media_id'];
            } elseif (($row['slide_index'] ?? null) !== null) {
                $slideIndex = (int) $row['slide_index'];
                if (array_key_exists($slideIndex, $slideMediaIds)) {
                    $selectedMediaId = (int) $slideMediaIds[$slideIndex];
                }
            }

            $item->update([
                'media_id' => $selectedMediaId,
                'is_parent_image' => $selectedMediaId !== null && $mainMediaId !== null && $selectedMediaId === $mainMediaId,
                'is_parent_slider_image' => $selectedMediaId !== null && array_key_exists($selectedMediaId, $slideMediaIdSet),
            ]);
        }
    }

    /**
     * @return array<string, array{media_id:int|null, slide_index:int|null, use_main_image:bool}>
     */
    private function normalizeVariantImageRows(mixed $variantImagesInput): array
    {
        if (! is_array($variantImagesInput)) {
            return [];
        }

        $rows = [];
        foreach ($variantImagesInput as $row) {
            if (! is_array($row)) {
                continue;
            }

            $key = strtoupper(trim((string) ($row['key'] ?? '')));
            if ($key === '') {
                continue;
            }

            $rows[$key] = [
                'media_id' => isset($row['media_id']) && $row['media_id'] !== '' ? (int) $row['media_id'] : null,
                'slide_index' => isset($row['slide_index']) && $row['slide_index'] !== '' ? (int) $row['slide_index'] : null,
                'use_main_image' => $this->normalizeBooleanInput($row['use_main_image'] ?? false),
            ];
        }

        return $rows;
    }

    /**
     * @return array<string, UploadedFile>
     */
    private function mapUploadedVariantFilesByKey(mixed $filesInput, mixed $keysInput): array
    {
        if (! is_array($filesInput) || ! is_array($keysInput)) {
            return [];
        }

        $mapped = [];
        foreach ($keysInput as $index => $rawKey) {
            $key = strtoupper(trim((string) $rawKey));
            if ($key === '') {
                continue;
            }

            $file = $filesInput[$index] ?? null;
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $mapped[$key] = $file;
        }

        return $mapped;
    }

    private function normalizeBooleanInput(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            if ($normalized === '1' || $normalized === 'true' || $normalized === 'on' || $normalized === 'yes') {
                return true;
            }
            if ($normalized === '0' || $normalized === 'false' || $normalized === 'off' || $normalized === 'no' || $normalized === '') {
                return false;
            }
        }

        return false;
    }
}
