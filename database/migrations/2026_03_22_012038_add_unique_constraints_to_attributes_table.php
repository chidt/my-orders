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
        Schema::table('attributes', function (Blueprint $table) {
            $table->unique(['name', 'site_id']);
            $table->unique(['code', 'site_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attributes', function (Blueprint $table) {
            $table->dropUnique(['name', 'site_id']);
            $table->dropUnique(['code', 'site_id']);
        });
    }
};
