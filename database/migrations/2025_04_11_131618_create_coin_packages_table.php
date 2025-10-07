<?php

use App\Models\Common\Country;
use App\Models\Common\Currency;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public $withinTransaction = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('coin_packages', function (Blueprint $table) {
            $table->id();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->integer('coin_amount');
            $table->decimal('price', 10, 2);
            $table->boolean('is_discount')->default(false);
            $table->decimal('discounted_price', 10, 2)->nullable();
            $table->foreignIdFor(Currency::class)->constrained();
            $table->boolean('is_active')->default(true);
            $table->foreignIdFor(Country::class)->constrained();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coin_packages');
    }
};
