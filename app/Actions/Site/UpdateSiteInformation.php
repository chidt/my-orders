<?php

namespace App\Actions\Site;

use App\Models\Site;

class UpdateSiteInformation
{
    /**
     * Update site information with validated data.
     */
    public function handle(Site $site, array $validatedData): Site
    {
        // Extract settings from validated data
        $settings = $site->settings ?? [];

        if (isset($validatedData['settings']['product_prefix'])) {
            $settings['product_prefix'] = $validatedData['settings']['product_prefix'];
        }

        // Update site with new data
        $site->update([
            'name' => $validatedData['name'],
            'slug' => $validatedData['slug'],
            'description' => $validatedData['description'] ?? null,
            'settings' => $settings,
        ]);

        return $site->refresh();
    }
}
