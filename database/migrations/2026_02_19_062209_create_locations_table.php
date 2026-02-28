<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50);
            $table->string('name');
            $table->boolean('is_default')->default(false);
            $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
            $table->integer('qty_in_stock')->default(0);
            $table->timestamps();

            // Unique constraint: code must be unique within each warehouse
            $table->unique(['warehouse_id', 'code'], 'locations_warehouse_code_unique');

            // Index for performance and default location queries
            $table->index('warehouse_id');
            $table->index(['warehouse_id', 'is_default']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
