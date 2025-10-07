<?php

namespace App\Jobs\GiftBasket;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GiftBasketGiftSalesUpdate implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public $gift,
        public $totalCost,
        public $quantity
    ) {}

    public function handle(): void
    {
        //gifts tablosunda bir giftin satış raporu için gerekli bilgiler güncelleniyor.
        $this->gift->increment('total_usage', $this->quantity);

        $this->gift->increment('total_sales', $this->totalCost); // toplam satış coin miktari arttırıldı
    }
}
