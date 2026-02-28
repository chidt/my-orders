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
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50);
            $table->string('name');
            $table->text('address');
            $table->foreignId('site_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamps();

            // Unique constraint: code must be unique within each site
            $table->unique(['site_id', 'code'], 'warehouses_site_code_unique');

            // Index for performance
            $table->index('site_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
