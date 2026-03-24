<?php

namespace App\Actions\Attribute;

use App\Models\Attribute;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DeleteAttribute
{
    /**
     * Delete the given attribute if allowed by business rules.
     */
    public function execute(Attribute $attribute): void
    {
        DB::transaction(function () use ($attribute): void {
            if (! $attribute->canBeDeleted()) {
                throw new RuntimeException('Không thể xóa thuộc tính đang được sử dụng bởi sản phẩm.');
            }

            $attribute->delete();
        });
    }
}
