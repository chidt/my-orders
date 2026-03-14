<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->unique();
            $table->boolean('is_parent_image');
            $table->boolean('is_parent_slider_image');
            $table->integer('qty_in_stock');
            $table->decimal('price', 10, 2);
            $table->decimal('partner_price', 10, 2)->nullable();
            $table->decimal('purchase_price', 10, 2);
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_items');
    }
};
