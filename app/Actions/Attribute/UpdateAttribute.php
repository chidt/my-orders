<?php

namespace App\Actions\Attribute;

use App\Models\Attribute;
use Illuminate\Support\Facades\DB;

class UpdateAttribute
{
    /**
     * Update the given attribute.
     */
    public function execute(Attribute $attribute, array $data): Attribute
    {
        return DB::transaction(function () use ($attribute, $data): Attribute {
            $attribute->fill([
                'name' => $data['name'],
                'code' => $data['code'],
                'description' => $data['description'] ?? null,
                'order' => $data['order'] ?? $attribute->order,
            ]);

            $attribute->save();

            return $attribute;
        });
    }
}
