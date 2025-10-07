<?php

namespace App\Jobs\GiftBasket;

use App\Helpers\Variable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\Relations\UserCoinTransaction;

class StoreCoinTransactionForGiftPurchase implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public $user,
        public $gift,
        public $giftBasket,
        public $totalCost
    ) {}

    public function handle(): void
    {
        $this->user->coin_transactions()->create([
            "amount" => -$this->totalCost,
            "wallet_type" => Variable::WALLET_TYPE_DEFAULT,
            "transaction_type" => UserCoinTransaction::TRANSACTION_TYPE_PURCHASE_GIFT,
            "gift_id"=> $this->gift?->id,
            "gift_basket_id" => $this->giftBasket->id
        ]);
    }
}
