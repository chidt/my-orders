<?php

namespace App\Actions\Product;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class DestroyProduct
{
    public function handle(Product $product): void
    {
        DB::transaction(function () use ($product) {
            $product->delete();
        });
    }
}

