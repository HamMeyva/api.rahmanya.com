<?php

namespace App\Livewire\Dashboard;

use App\Models\Agora\AgoraChannel;
use Carbon\Carbon;
use Livewire\Component;
use MongoDB\BSON\UTCDateTime;
use Illuminate\Support\Facades\Cache;

class LiveStreamActivitiesChart extends Component
{
    public $isLoading = true, $categories = [], $data = [];

    public function loadData(): void
    {
        $result = $this->getLiveStreamActivitiesData();

        $this->categories = $result['categories'];
        $this->data = $result['data'];

        $this->dispatch('liveStreamActivitiesDataLoaded', ['categories' => $result['categories'], 'data' => $result['data']]);

        $this->isLoading = false;
    }

    public function render()
    {
        return view('livewire.dashboard.live-stream-activities-chart');
    }

    public function getLiveStreamActivitiesData(): array
    {
        Carbon::setLocale('tr');
        $now = Carbon::today();

        $sevenDaysAgo = $now->copy()->subDays(6)->startOfDay();
        $end = $now->copy()->endOfDay();

        // Şimdiki haftanın pipeline’ı
        $pipelineThisWeek = [
            [
                '$match' => [
                    'started_at' => [
                        '$gte' => new UTCDateTime($sevenDaysAgo->getTimestamp() * 1000),
                        '$lte' => new UTCDateTime($end->getTimestamp() * 1000),
                    ]
                ]
            ],
            [
                '$group' => [
                    '_id' => [
                        '$dateToString' => [
                            'format' => '%Y-%m-%d',
                            'date' => '$started_at',
                        ]
                    ],
                    'item_count' => ['$sum' => 1]
                ]
            ],
            [
                '$sort' => ['_id' => 1]
            ]
        ];

        $resultsThisWeek = AgoraChannel::raw(function ($collection) use ($pipelineThisWeek) {
            return $collection->aggregate($pipelineThisWeek)->toArray();
        });

        // Geçen ayın aynı günleri için tarih aralığını al
        $sevenDaysAgoLastMonth = $sevenDaysAgo->copy()->subMonthNoOverflow();
        $endLastMonth = $end->copy()->subMonthNoOverflow();

        $pipelineLastMonth = [
            [
                '$match' => [
                    'started_at' => [
                        '$gte' => new UTCDateTime($sevenDaysAgoLastMonth->getTimestamp() * 1000),
                        '$lte' => new UTCDateTime($endLastMonth->getTimestamp() * 1000),
                    ]
                ]
            ],
            [
                '$group' => [
                    '_id' => [
                        '$dateToString' => [
                            'format' => '%Y-%m-%d',
                            'date' => '$started_at',
                        ]
                    ],
                    'item_count' => ['$sum' => 1]
                ]
            ],
            [
                '$sort' => ['_id' => 1]
            ]
        ];

        $resultsLastMonth = AgoraChannel::raw(function ($collection) use ($pipelineLastMonth) {
            return $collection->aggregate($pipelineLastMonth)->toArray();
        });

        $days = [];
        $todayData = [];
        $lastMonthData = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i);
            $key = $date->format('Y-m-d');
            $days[] = $date->translatedFormat('l');

            $todayCount = collect($resultsThisWeek)->firstWhere('_id', $key)->item_count ?? 0;
            $lastMonthDate = $date->copy()->subMonthNoOverflow()->format('Y-m-d');
            $lastMonthCount = collect($resultsLastMonth)->firstWhere('_id', $lastMonthDate)->item_count ?? 0;

            $todayData[] = $todayCount;
            $lastMonthData[] = $lastMonthCount;
        }

        return [
            'categories' => $days,
            'data' => [
                [
                    'name' => "Bugün",
                    'data' => $todayData
                ],
                [
                    'name' => "Geçen Ay Bugün",
                    'data' => $lastMonthData
                ],
            ],
        ];
    }
}
