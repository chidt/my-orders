<?php

namespace App\Actions\Category;

use App\Models\Category;

class DeleteCategory
{
    /**
     * Delete a category if it's safe to do so.
     */
    public function handle(Category $category, bool $force = false): bool
    {
        // Check if category can be deleted
        if (! $force && ! $category->canDelete()) {
            throw new \InvalidArgumentException(
                'Cannot delete category: it has products or child categories. '.
                'Please move or delete them first, or use force delete.'
            );
        }

        // If forcing deletion, handle children and products
        if ($force) {
            // Move children to parent (or make them root categories)
            $children = $category->children;
            foreach ($children as $child) {
                $child->update(['parent_id' => $category->parent_id]);
            }

            // Note: Products will be handled by foreign key constraints
            // or you might want to move them to a default category
        }

        return $category->delete();
    }
}
