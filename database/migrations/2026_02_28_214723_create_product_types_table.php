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
        Schema::create('product_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->integer('order')->default(0);
            $table->boolean('show_on_front')->default(false);
            $table->string('color', 7)->default('#3b82f6');
            $table->unsignedBigInteger('site_id');
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('site_id')->references('id')->on('sites')->onDelete('cascade');

            // Indexes
            $table->index('site_id');
            $table->index('order');

            // Unique constraints
            $table->unique(['name', 'site_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_types');
    }
};
