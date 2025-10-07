<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_discounts', function (Blueprint $table) {
            $table->id();
            $table->timestampsTz();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->string('source_id');
            $table->string('description')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('coupon_code')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_discounts');
    }
};
