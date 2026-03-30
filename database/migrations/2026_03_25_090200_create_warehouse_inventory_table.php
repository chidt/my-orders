<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->integer('current_qty')->default(0);
            $table->integer('reserved_qty')->default(0);
            $table->integer('pre_order_qty')->default(0);
            $table->decimal('avg_cost', 12, 2)->nullable();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamp('last_updated')->nullable();
            $table->timestamps();

            $table->unique(['product_item_id', 'location_id', 'site_id']);
            $table->index(['site_id', 'product_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_inventory');
    }
};
