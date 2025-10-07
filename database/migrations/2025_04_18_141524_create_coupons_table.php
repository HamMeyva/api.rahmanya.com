<?php

use App\Models\Common\Country;
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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->timestampsTz();
            $table->string('code', 50);
            $table->enum('discount_type', ['percentage', 'fixed']);
            $table->foreignIdFor(Currency::class)->nullable()->constrained();
            $table->decimal('discount_amount', 10, 2);
            $table->dateTimeTz('start_date')->nullable();
            $table->dateTimeTz('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('max_usage')->nullable();
            $table->integer('usage_count')->default(0);

            $table->foreignIdFor(Country::class)->constrained();

            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
