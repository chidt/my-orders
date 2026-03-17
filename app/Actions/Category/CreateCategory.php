<?php

namespace App\Actions\Category;

use App\Models\Category;
use Illuminate\Support\Str;

class CreateCategory
{
    /**
     * Create a new category with validated data.
     */
    public function handle(array $validatedData, int $siteId): Category
    {
        // Auto-generate slug if not provided
        if (empty($validatedData['slug'])) {
            $validatedData['slug'] = Str::slug($validatedData['name']);
        }

        // Ensure parent belongs to the same site if provided
        if (isset($validatedData['parent_id'])) {
            $parent = Category::forSite($siteId)->findOrFail($validatedData['parent_id']);

            // Validate max depth (3 levels)
            if ($parent->depth >= 2) {
                throw new \InvalidArgumentException('Categories can only be nested 3 levels deep.');
            }
        }

        // Set default values
        $validatedData['site_id'] = $siteId;
        $validatedData['order'] = $validatedData['order'] ?? 0;
        $validatedData['is_active'] = $validatedData['is_active'] ?? true;

        return Category::create($validatedData);
    }
}
