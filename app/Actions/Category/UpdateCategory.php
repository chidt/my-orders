<?php

namespace App\Actions\Category;

use App\Models\Category;
use Illuminate\Support\Str;

class UpdateCategory
{
    /**
     * Update an existing category with validated data.
     */
    public function handle(Category $category, array $validatedData): Category
    {
        // Auto-generate slug if name changed but slug not provided
        if (isset($validatedData['name']) && $validatedData['name'] !== $category->name) {
            if (empty($validatedData['slug'])) {
                $validatedData['slug'] = Str::slug($validatedData['name']);
            }
        }

        // Validate parent change
        if (isset($validatedData['parent_id']) && $validatedData['parent_id'] !== $category->parent_id) {
            // Cannot set parent to self or descendant
            if ($validatedData['parent_id'] == $category->id) {
                throw new \InvalidArgumentException('Category cannot be its own parent.');
            }

            if ($validatedData['parent_id']) {
                $newParent = Category::forSite($category->site_id)->findOrFail($validatedData['parent_id']);

                // Check if new parent is a descendant
                if ($newParent->isDescendantOf($category)) {
                    throw new \InvalidArgumentException('Cannot set a descendant as parent (would create circular reference).');
                }

                // Validate max depth
                if ($newParent->depth >= 2) {
                    throw new \InvalidArgumentException('Categories can only be nested 3 levels deep.');
                }
            }
        }

        $category->update($validatedData);

        return $category->refresh();
    }
}
