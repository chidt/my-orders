<?php

namespace App\Actions\Attribute;

use App\Models\Attribute;
use App\Models\Site;
use Illuminate\Support\Facades\DB;

class StoreAttribute
{
    /**
     * Create a new attribute for the given site.
     */
    public function execute(array $data, Site $site): Attribute
    {
        return DB::transaction(function () use ($data, $site): Attribute {
            $attribute = new Attribute([
                'name' => $data['name'],
                'code' => $data['code'],
                'description' => $data['description'] ?? null,
                'order' => $data['order'] ?? 0,
            ]);

            $attribute->site()->associate($site);
            $attribute->save();

            return $attribute;
        });
    }
}
