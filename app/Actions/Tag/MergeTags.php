<?php

namespace App\Actions\Tag;

use App\Models\Tag;
use Illuminate\Support\Facades\DB;

class MergeTags
{
    /**
     * Merge multiple tags into one tag.
     */
    public function handle(Tag $primaryTag, array $tagIds, int $siteId): Tag
    {
        return DB::transaction(function () use ($primaryTag, $tagIds, $siteId) {
            // Get tags to merge (excluding primary tag)
            $tagsToMerge = Tag::forSite($siteId)
                ->whereIn('id', $tagIds)
                ->where('id', '!=', $primaryTag->id)
                ->get();

            foreach ($tagsToMerge as $tag) {
                // Move all products from this tag to primary tag
                $productIds = $tag->products()->pluck('products.id');

                foreach ($productIds as $productId) {
                    // Add to primary tag if not already attached
                    if (! $primaryTag->products()->where('products.id', $productId)->exists()) {
                        $primaryTag->products()->attach($productId);
                    }
                }

                // Delete the merged tag
                $tag->delete();
            }

            return $primaryTag->refresh();
        });
    }
}
