<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_code')->unique();
            $table->foreignId('vendor_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('product_name');
            $table->string('stock_status')->default('in_stock');
            $table->integer('quantity')->default(0);
            $table->integer('quantity_threshold')->default(0);
            $table->decimal('total_cost_price', 10, 2)->default(0);
            $table->decimal('gst', 10, 2)->default(0);
            $table->decimal('total_cost_price_with_gst', 10, 2)->default(0);
            $table->decimal('cost_price_per_quantity', 10, 2)->default(0);
            $table->decimal('selling_price_per_quantity', 10, 2)->default(0);
            $table->boolean('is_service')->default(false);
            $table->index(['stock_status', 'quantity']);
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
