<?php

namespace App\Actions\Category;

use App\Models\Category;
use Illuminate\Support\Facades\DB;

class ReorderCategories
{
    /**
     * Update category order via drag & drop.
     */
    public function handle(array $orderData, int $siteId): bool
    {
        return DB::transaction(function () use ($orderData, $siteId) {
            foreach ($orderData as $item) {
                // Validate that category belongs to the site
                $category = Category::forSite($siteId)->findOrFail($item['id']);

                $updateData = ['order' => $item['order']];

                // If parent is being changed
                if (isset($item['parent_id'])) {
                    if ($item['parent_id']) {
                        // Validate parent belongs to same site
                        $parent = Category::forSite($siteId)->findOrFail($item['parent_id']);
                        $updateData['parent_id'] = $parent->id;
                    } else {
                        $updateData['parent_id'] = null;
                    }
                }

                $category->update($updateData);
            }

            return true;
        });
    }
}
