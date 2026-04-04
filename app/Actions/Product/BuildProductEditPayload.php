<?php

namespace App\Actions\Product;

use App\Models\Product;
use App\Models\ProductAttributeValue;
use App\Models\ProductItem;

class BuildProductEditPayload
{
    public function execute(Product $productModel): array
    {
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

        $productAttributes = $productModel->productAttributeValues
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
            })->values()->all();

        return [
            'mainImage' => $mainImage,
            'slideImages' => $slideImages,
            'variantUploadImages' => $variantUploadImages,
            'variantImages' => $variantImages,
            'childProducts' => $childProducts,
            'lockedProductAttributeValueIds' => $lockedProductAttributeValueIds,
            'productAttributes' => $productAttributes,
        ];
    }

    private function canDeleteChildProduct(ProductItem $childProduct): bool
    {
        $hasOrderDetails = $childProduct->relationLoaded('orderDetails')
            ? $childProduct->orderDetails->isNotEmpty()
            : $childProduct->orderDetails()->exists();

        return ! $hasOrderDetails;
    }
}
