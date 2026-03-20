<?php

namespace App\Actions\Supplier;

use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DeleteSupplier
{
    /**
     * Delete the given supplier if allowed by business rules.
     */
    public function execute(Supplier $supplier): void
    {
        DB::transaction(function () use ($supplier): void {
            if (! $supplier->canBeDeleted()) {
                throw new RuntimeException('Không thể xóa nhà cung cấp đã có sản phẩm.');
            }

            $supplier->delete();
        });
    }
}
