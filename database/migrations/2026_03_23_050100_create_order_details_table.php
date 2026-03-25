<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_details', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('payment_status');
            $table->unsignedBigInteger('payment_request_id')->nullable();
            $table->tinyInteger('status');
            $table->tinyInteger('fulfillment_status')->default(0);
            $table->integer('qty');
            $table->decimal('price', 12, 2);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('addition_price', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->text('note')->nullable();
            $table->foreignId('product_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('purchase_request_detail_id')->nullable();
            $table->dateTime('order_date')->nullable();
            $table->dateTime('expected_fulfillment_date')->nullable();
            $table->json('extra_attributes')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index(['site_id', 'product_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_details');
    }
};
