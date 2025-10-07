<?php

namespace App\Jobs\LiveStreamGift;

use App\Models\User;
use App\Helpers\Variable;
use App\Models\Agora\AgoraChannel;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\Relations\UserCoinTransaction;

class TransferCoinsToStreamerWallet implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public AgoraChannel $agoraChannel,
        public User $recipientUser,
        public $senderUserId,
        public $giftId,
        public $totalCost
    ) {}

    public function handle(): void
    {
        //hediye alındığında streamer bakiyesine transfer edilir.
        $this->recipientUser->coin_transactions()->create([
            "amount" => $this->totalCost,
            "wallet_type" => Variable::WALLET_TYPE_EARNED,
            "transaction_type" => UserCoinTransaction::TRANSACTION_TYPE_RECEIVE_GIFT,
            "gift_id" => $this->giftId,
            "related_user_id" => $this->senderUserId,
        ]);

        $this->recipientUser->increment('earned_coin_balance', $this->totalCost);
    }
}
