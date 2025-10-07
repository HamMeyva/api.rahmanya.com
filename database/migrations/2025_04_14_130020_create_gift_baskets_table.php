<?php

use App\Models\Gift;
use App\Models\User;
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
        Schema::create('gift_baskets', function (Blueprint $table) {
            $table->id();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Gift::class)->nullable()->constrained()->nullOnDelete();
            $table->integer('custom_unit_price')->nullable(); // Seçilen hediyede is_custom_gifts true ise. Hediye fiyatı olarak buraya bakılır (Zarf veya benzeri özel ürün).
            $table->integer('quantity')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gift_baskets');
    }
};
