<?php

use App\Models\Gift;
use App\Models\User;
use App\Models\GiftBasket;
use App\Models\Coin\CoinPackage;
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
        Schema::create('user_coin_transactions', function (Blueprint $table) {
            $table->id();
            $table->timestampsTz();
            $table->softDeletes();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();

            $table->integer('amount');
            $table->integer('wallet_type'); // default wallet (yükleme yaptıgı, hediye gönderdiği, çekilemez bakiye), earning wallet (kullanıcının çekilebilir bakiyesi)
            $table->integer('transaction_type'); // deposit, send_gift, receive_gift, withdraw

            $table->foreignIdFor(CoinPackage::class)->nullable()->constrained()->nullOnDelete(); //deposit yapıldı ise
            $table->foreignIdFor(Gift::class)->nullable()->constrained()->nullOnDelete(); //send_gift veya receive_gift yapıldı ise
            $table->foreignUuid('related_user_id')->nullable()->constrained('users')->nullOnDelete(); //send_gift veya receive_gift yapıldı ise
            $table->foreignIdFor(GiftBasket::class)->nullable()->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_coin_transactions');
    }
};
