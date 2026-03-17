<?php

namespace App\Actions\Tag;

use App\Models\Tag;
use Illuminate\Support\Str;

class CreateTag
{
    /**
     * Create a new tag with validated data.
     */
    public function handle(array $validatedData, int $siteId): Tag
    {
        // Auto-generate slug if not provided
        if (empty($validatedData['slug'])) {
            $validatedData['slug'] = Str::slug($validatedData['name']);
        }

        // Ensure slug is unique within site
        $originalSlug = $validatedData['slug'];
        $counter = 1;

        while (Tag::forSite($siteId)->where('slug', $validatedData['slug'])->exists()) {
            $validatedData['slug'] = $originalSlug.'-'.$counter;
            $counter++;
        }

        // Set site_id
        $validatedData['site_id'] = $siteId;

        return Tag::create($validatedData);
    }
}
