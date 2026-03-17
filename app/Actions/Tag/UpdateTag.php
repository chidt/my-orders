<?php

namespace App\Actions\Tag;

use App\Models\Tag;
use Illuminate\Support\Str;

class UpdateTag
{
    /**
     * Update an existing tag with validated data.
     */
    public function handle(Tag $tag, array $validatedData): Tag
    {
        // Auto-generate slug if name changed but slug not provided
        if (isset($validatedData['name']) && $validatedData['name'] !== $tag->name) {
            if (empty($validatedData['slug'])) {
                $validatedData['slug'] = Str::slug($validatedData['name']);
            }
        }

        // Ensure slug is unique within site (excluding current tag)
        if (isset($validatedData['slug']) && $validatedData['slug'] !== $tag->slug) {
            $originalSlug = $validatedData['slug'];
            $counter = 1;

            while (Tag::forSite($tag->site_id)
                ->where('slug', $validatedData['slug'])
                ->where('id', '!=', $tag->id)
                ->exists()) {
                $validatedData['slug'] = $originalSlug.'-'.$counter;
                $counter++;
            }
        }

        $tag->update($validatedData);

        return $tag->refresh();
    }
}
