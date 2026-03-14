<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('supplier_code')->nullable();
            $table->foreignId('product_type_id')->constrained('product_types')->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->integer('qty_in_stock');
            $table->decimal('weight', 8, 2)->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('partner_price', 10, 2)->nullable();
            $table->decimal('purchase_price', 10, 2);
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->datetime('order_closing_date')->nullable();
            $table->foreignId('default_location_id')->constrained('locations')->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->cascadeOnDelete();
            $table->json('extra_attributes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
