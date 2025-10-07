<?php

namespace App\Jobs\LiveStreamGift;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class UpdateAgoraChannelGiftStats implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public $agoraChannel,
        public $quantity,
        public $totalCost
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->agoraChannel) {
            $this->agoraChannel->increment('total_gifts', $this->quantity);
            $this->agoraChannel->increment('total_coins_earned', $this->totalCost);
        }
    }
}
