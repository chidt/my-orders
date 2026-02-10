<?php

namespace App\Actions\Site;

use Illuminate\Support\Str;

class GenerateSlugFromName
{
    /**
     * Generate URL-friendly slug from site name.
     */
    public function handle(string $name): string
    {
        // Generate basic slug
        $slug = Str::slug($name);

        // Limit to reasonable length
        return substr($slug, 0, 50);
    }

    /**
     * Generate unique slug by checking database.
     */
    public function handleUnique(string $name, ?int $excludeId = null): string
    {
        $baseSlug = $this->handle($name);
        $slug = $baseSlug;
        $counter = 1;

        while ($this->slugExists($slug, $excludeId)) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Check if slug exists in database.
     */
    protected function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $query = \App\Models\Site::where('slug', $slug);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
