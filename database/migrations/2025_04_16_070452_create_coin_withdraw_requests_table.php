<?php

use App\Models\User;
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
        Schema::create('coin_withdrawal_requests', function (Blueprint $table) {
            $table->id();
            $table->timestampsTz();
            $table->foreignIdFor(User::class)->nullable()->constrained()->nullOnDelete();

            $table->integer('coin_amount');
            $table->decimal('coin_unit_price', 11, 2);
            $table->decimal('coin_total_price', 11, 2);

            $table->foreignIdFor(Currency::class)->nullable()->constrained()->nullOnDelete();

            $table->smallInteger('wallet_type_id');
            $table->smallInteger('status_id');
            $table->text('reject_reason')->nullable();
            $table->timestampTz('approved_at')->nullable();
            $table->timestampTz('rejected_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coin_withdrawal_requests');
    }
};
