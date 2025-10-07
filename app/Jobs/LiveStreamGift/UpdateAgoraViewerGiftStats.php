<?php

namespace App\Jobs\LiveStreamGift;

use App\Models\Agora\AgoraChannelViewer;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateAgoraViewerGiftStats implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public AgoraChannelViewer $senderViewer,
        public AgoraChannelViewer $recipientViewer,
        public $quantity,
        public $totalCost
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->senderViewer) {
            $this->senderViewer->increment('total_sent_gift_count', $this->quantity);
            $this->senderViewer->increment('total_sent_coin_value', $this->totalCost);
        }

        if ($this->recipientViewer) {
            $this->recipientViewer->increment('total_received_gift_count', $this->quantity);
            $this->recipientViewer->increment('total_received_coin_value', $this->totalCost);
        }
    }
}
