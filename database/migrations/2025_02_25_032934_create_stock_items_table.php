<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_code')->unique();
            $table->foreignId('vendor_id')->default('0');
            $table->string('product_name');
            $table->string('stock_status');
            $table->decimal('sale_price', 10, 2);
            $table->decimal('gst', 10, 2);
            $table->decimal('total', 10, 2);
            $table->decimal('inventory_value', 10, 2)->nullable();
            $table->integer('quantity')->default(0);
            $table->integer('quantity_threshold')->default(0);
            $table->boolean('is_service')->default(false);
            $table->boolean('is_liquid')->default(false);
            $table->string('product_type')->nullable();
            $table->string('unit_type')->nullable();
            $table->decimal('volume_per_unit', 10, 2)->nullable();
            $table->decimal('remaining_volume', 10, 2)->nullable();
            $table->integer('remaining_quantity', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_items');
    }
};
