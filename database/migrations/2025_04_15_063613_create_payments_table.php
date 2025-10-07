<?php

use App\Models\User;
use App\Models\Ad\Advertiser;
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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->timestampsTz();

            $table->string('payable_type');
            $table->string('payable_id'); // Mongo ObjectId ya da UUID ya da id
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->timestampTz('paid_at')->nullable();
            $table->smallInteger('status_id')->default(1);
            $table->string('transaction_id')->nullable();
            $table->string('refund_id')->nullable();
            $table->smallInteger('channel_id')->nullable();
            $table->text('failure_reason')->nullable();

            $table->foreignIdFor(User::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(Advertiser::class)->nullable()->constrained()->nullOnDelete();

            $table->foreignIdFor(Currency::class)->nullable()->constrained()->nullOnDelete();

            $table->string('iyzico_payment_id')->nullable();
            $table->string('conversation_data')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
