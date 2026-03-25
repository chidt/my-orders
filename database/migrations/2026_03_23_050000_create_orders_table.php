<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('payment_status');
            $table->string('order_number')->unique();
            $table->dateTime('order_date');
            $table->tinyInteger('customer_type');
            $table->tinyInteger('status');
            $table->tinyInteger('shipping_payer');
            $table->string('phone');
            $table->text('shipping_note')->nullable();
            $table->text('order_note')->nullable();
            $table->tinyInteger('sale_channel');
            $table->foreignId('shipping_address_id')->nullable()->constrained('addresses')->nullOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->index(['site_id', 'status']);
            $table->index(['customer_id', 'order_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
