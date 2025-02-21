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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_type');
            $table->date('date');
            $table->float('amount');
            $table->text('description');
            $table->string('payment_method');
            $table->string('vendor');
            $table->string('invoice_number');
            $table->string('category');
            $table->string('attachment');
            $table->text('notes');
            $table->integer('unit_price');
            $table->integer('qty');
            $table->float('rate');
            $table->float('gst');
            $table->string('unit_price_with_gst');
            $table->string('total_expenses');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};


/**
 * total exps = qty * unit_price
 * unit price = rate + gst
 *
 *
 *
 */
