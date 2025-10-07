<?php

use App\Models\Common\Currency;
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
        Schema::dropIfExists('coin_withdrawal_prices');

        Schema::create('coin_withdrawal_prices', function (Blueprint $table) {
            $table->id();
            $table->timestampsTz();
            $table->foreignIdFor(Currency::class)->constrained()->cascadeOnDelete();
            $table->decimal('coin_unit_price', 11, 2);


            $table->unique('currency_id');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coin_withdrawal_prices');
    }
};
