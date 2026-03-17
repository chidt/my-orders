<?php

namespace App\Actions\Tag;

use App\Models\Tag;

class DeleteTag
{
    /**
     * Delete a tag if it's safe to do so.
     */
    public function handle(Tag $tag, bool $force = false): bool
    {
        // Check if tag can be deleted
        if (! $force && ! $tag->canDelete()) {
            throw new \InvalidArgumentException(
                'Cannot delete tag: it is being used by products. '.
                'Please remove it from products first, or use force delete.'
            );
        }

        // If forcing deletion, detach from all products
        if ($force) {
            $tag->products()->detach();
        }

        return $tag->delete();
    }
}
