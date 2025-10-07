<?php

namespace App\Livewire\Dashboard;

use Carbon\Carbon;
use App\Models\Story;
use Livewire\Component;
use App\Helpers\UTCDateTime;
use Illuminate\Support\Facades\Cache;

class StoryActivitiesChart extends Component
{
    public $isLoading = true, $categories = [], $data = [];

    public function loadData(): void
    {
        $result = $this->getStoryActivitiesData();

        $this->categories = $result['categories'];
        $this->data = $result['data'];

        $this->dispatch('storyActivitiesDataLoaded', ['categories' => $result['categories'], 'data' => $result['data']]);

        $this->isLoading = false;
    }

    public function render()
    {
        return view('livewire.dashboard.story-activities-chart');
    }

    public function getStoryActivitiesData(): array
    {
        return Cache::remember('dashboard:story-activities-chart', 3600, function () {
            Carbon::setLocale('tr');
            $now = Carbon::today();

            $sevenDaysAgo = $now->copy()->subDays(6)->startOfDay();
            $end = $now->copy()->endOfDay();

            // Şimdiki haftanın pipeline’ı
            $pipelineThisWeek = [
                [
                    '$match' => [
                        'created_at' => [
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
                                'date' => '$created_at',
                            ]
                        ],
                        'video_count' => ['$sum' => 1]
                    ]
                ],
                [
                    '$sort' => ['_id' => 1]
                ]
            ];

            $resultsThisWeek = Story::raw(function ($collection) use ($pipelineThisWeek) {
                return $collection->aggregate($pipelineThisWeek)->toArray();
            });

            // Geçen ayın aynı günleri için tarih aralığını al
            $sevenDaysAgoLastMonth = $sevenDaysAgo->copy()->subMonthNoOverflow();
            $endLastMonth = $end->copy()->subMonthNoOverflow();

            $pipelineLastMonth = [
                [
                    '$match' => [
                        'created_at' => [
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
                                'date' => '$created_at',
                            ]
                        ],
                        'video_count' => ['$sum' => 1]
                    ]
                ],
                [
                    '$sort' => ['_id' => 1]
                ]
            ];

            $resultsLastMonth = Story::raw(function ($collection) use ($pipelineLastMonth) {
                return $collection->aggregate($pipelineLastMonth)->toArray();
            });

            $days = [];
            $todayData = [];
            $lastMonthData = [];

            for ($i = 6; $i >= 0; $i--) {
                $date = $now->copy()->subDays($i);
                $key = $date->format('Y-m-d');
                $days[] = $date->translatedFormat('l');

                $todayCount = collect($resultsThisWeek)->firstWhere('_id', $key)->video_count ?? 0;
                $lastMonthDate = $date->copy()->subMonthNoOverflow()->format('Y-m-d');
                $lastMonthCount = collect($resultsLastMonth)->firstWhere('_id', $lastMonthDate)->video_count ?? 0;

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
        });
    }
}
