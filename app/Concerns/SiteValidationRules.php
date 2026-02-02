<?php

namespace App\Concerns;

use App\Models\Site;
use Illuminate\Validation\Rule;

trait SiteValidationRules
{
    /**
     * Get the validation rules used to validate sites.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    protected function siteRules(?int $userId = null): array
    {
        $rules = [
            'site_description' => ['nullable', 'string', 'max:2000'],
        ];

        if ($userId === null) {
            $rules['site_name'] = ['required', 'string', 'max:255'];
            $rules['site_slug'] = ['required', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/', Rule::unique(Site::class, 'slug')];
        }

        return $rules;
    }

    /**
     * Generate a unique slug from the given name.
     */
    protected function generateSlug(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');

        $originalSlug = $slug;
        $counter = 1;

        while (Site::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }

        return $slug;
    }
}
