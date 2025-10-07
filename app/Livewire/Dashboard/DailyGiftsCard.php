<?php

namespace App\Livewire\Dashboard;

use Carbon\Carbon;
use Livewire\Component;
use App\Models\Agora\AgoraChannelGift;
use MongoDB\BSON\UTCDateTime;

class DailyGiftsCard extends Component
{
    public $count,
        $amount,
        $formattedCount,
        $formattedAmount,
        $targetGiftQuantity = 500; //günlük hediye adet hedefi

    public function mount(): void
    {
        $this->count = null;
        $this->amount = null;
    }

    public function loadData(): void
    {
        $startOfDay = Carbon::today();
        $endOfDay = Carbon::tomorrow();

        $pipeline = [
            [
                '$match' => [
                    'created_at' => [
                        '$gte' => new UTCDateTime($startOfDay),
                        '$lte' => new UTCDateTime($endOfDay),
                    ]
                ]
            ],
            [
                '$group' => [
                    '_id' => null,
                    'total_quantity' => ['$sum' => '$quantity'],
                    'total_amount' => ['$sum' => '$coin_value'],
                ]
            ]
        ];

        $result = AgoraChannelGift::raw(function ($collection) use ($pipeline) {
            return $collection->aggregate($pipeline);
        });

        $this->count = $result[0]['total_quantity'] ?? 0;
        $this->amount = $result[0]['total_amount'] ?? 0;
    }


    public function render()
    {
        return view('livewire.dashboard.daily-gifts-card');
    }

    public function getRemainingGiftsToTargetProperty(): int
    {
        return max($this->targetGiftQuantity - ($this->count ?? 0), 0);
    }

    public function getRemainingGiftsToTargetPercentageProperty(): int
    {
        $percent = (($this->count ?? 0) / $this->targetGiftQuantity) * 100;
        return (int) min(max($percent, 0), 100);
    }
}
