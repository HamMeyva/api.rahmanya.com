<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use Carbon\Carbon;
use App\Models\UserSessionLog;
use MongoDB\BSON\UTCDateTime;

class ActiveUsersChart extends Component
{
    public $isLoading = true, $categories = [], $data = [];

    public function loadData(): void
    {
        $result = $this->getActiveUsers();

        $this->categories = $result['categories'];
        $this->data = $result['data'];

        $this->dispatch('activeUsersDataLoaded', ['categories' => $result['categories'], 'data' => $result['data']]);

        $this->isLoading = false;
    }


    public function render()
    {
        return view('livewire.dashboard.active-users-chart');
    }

    public function getActiveUsers()
    {
        Carbon::setLocale('tr');
        $now = Carbon::today();
        $sevenDaysAgo = $now->copy()->subDays(6)->startOfDay();
        $end = $now->copy()->endOfDay();

        // Şimdiki haftanın pipeline’ı
        $pipelineThisWeek = [
            [
                '$match' => [
                    'start_at' => [
                        '$gte' => new UTCDateTime($sevenDaysAgo->getTimestamp() * 1000),
                        '$lte' => new UTCDateTime($end->getTimestamp() * 1000),
                    ]
                ]
            ],
            [
                '$group' => [
                    '_id' => [
                        'day' => ['$dateToString' => ['format' => '%Y-%m-%d', 'date' => '$start_at']],
                        'user_id' => '$user_id'
                    ]
                ]
            ],
            [
                '$group' => [
                    '_id' => '$_id.day',
                    'unique_user_count' => ['$sum' => 1]
                ]
            ],
            [
                '$sort' => ['_id' => 1]
            ]
        ];

        $resultsThisWeek = UserSessionLog::raw(function ($collection) use ($pipelineThisWeek) {
            return $collection->aggregate($pipelineThisWeek)->toArray();
        });

        // Geçen ayın aynı günleri için tarih aralığını al
        $sevenDaysAgoLastMonth = $sevenDaysAgo->copy()->subMonthNoOverflow();
        $endLastMonth = $end->copy()->subMonthNoOverflow();

        $pipelineLastMonth = [
            [
                '$match' => [
                    'start_at' => [
                        '$gte' => new UTCDateTime($sevenDaysAgoLastMonth->getTimestamp() * 1000),
                        '$lte' => new UTCDateTime($endLastMonth->getTimestamp() * 1000),
                    ]
                ]
            ],
            [
                '$group' => [
                    '_id' => [
                        'day' => ['$dateToString' => ['format' => '%Y-%m-%d', 'date' => '$start_at']],
                        'user_id' => '$user_id'
                    ]
                ]
            ],
            [
                '$group' => [
                    '_id' => '$_id.day',
                    'unique_user_count' => ['$sum' => 1]
                ]
            ],
            [
                '$sort' => ['_id' => 1]
            ]
        ];

        $resultsLastMonth = UserSessionLog::raw(function ($collection) use ($pipelineLastMonth) {
            return $collection->aggregate($pipelineLastMonth)->toArray();
        });

        $days = [];
        $todayData = [];
        $lastMonthData = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i);
            $key = $date->format('Y-m-d');
            $days[] = $date->translatedFormat('l');

            $todayCount = collect($resultsThisWeek)->firstWhere('_id', $key)->unique_user_count ?? 0;
            $lastMonthDate = $date->copy()->subMonthNoOverflow()->format('Y-m-d');
            $lastMonthCount = collect($resultsLastMonth)->firstWhere('_id', $lastMonthDate)->unique_user_count ?? 0;

            $todayData[] = $todayCount;
            $lastMonthData[] = $lastMonthCount;
        }

        return [
            'categories' => $days,
            'data' => [
                [
                    'name' => "Bugün",
                    'data' => $todayData,
                ],
                [
                    'name' => "Geçen Ay Bugün",
                    'data' => $lastMonthData,
                ],
            ],
        ];


        /* return [
            'categories' => ["Pazartesi", "Salı", "Çarşamba", "Perşembe", "Cuma", "Cumartesi", "Pazar"],
            'data' => [
                [
                    'name' => "Bugün",
                    'data' => [15605, 159000, 155, 985, 16, 2689, 18059]
                ],
                [
                    'name' => "Geçen Ay Bugün",
                    'data' => [150, 220, 500, 69, 22, 660, 12366]
                ],
            ],
        ];*/
    }
}
