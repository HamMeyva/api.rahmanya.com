<?php

namespace App\Http\Controllers\Admin\Reports\LiveStreams;

use Illuminate\Http\Request;
use MongoDB\BSON\UTCDateTime;
use App\Models\Relations\Team;
use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;
use App\Models\Agora\AgoraChannel;
use Illuminate\Contracts\View\View;
use App\Http\Controllers\Controller;
use App\Models\Agora\AgoraChannelViewer;

class LiveStreamReportController extends Controller
{
    public function index(): View
    {
        $now = Carbon::now();

        return view('admin.pages.reports.live-streams.index', compact('now'));
    }

    public function getDurationChartData(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
        ]);

        if ($request->filled('start_date')) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = $startDate->copy()->addDays(6)->endOfDay(); // 6 gün sonrasını alıyoruz
        } else {
            $endDate = Carbon::now()->endOfDay();
            $startDate = $endDate->copy()->subDays(6)->startOfDay(); // 6 gün öncesinden bugüne
        }

        $pipeline = [
            [
                '$match' => [
                    'started_at' => [
                        '$gte' => new UTCDateTime($startDate),
                        '$lte' => new UTCDateTime($endDate)
                    ]
                ]
            ],
            [
                '$addFields' => [
                    'day' => [
                        '$dateToString' => [
                            'format' => '%Y-%m-%d',
                            'date' => '$started_at',
                            'timezone' => 'Europe/Istanbul'
                        ]
                    ]
                ]
            ],
            [
                '$group' => [
                    '_id' => '$day',
                    'total_duration' => ['$sum' => '$duration']
                ]
            ],
            [
                '$sort' => ['_id' => 1]
            ]
        ];

        $results = AgoraChannel::raw(function ($collection) use ($pipeline) {
            return $collection->aggregate($pipeline);
        });

        // Son 7 günün tarihlerini al
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $day = $startDate->copy()->addDays($i)->format('Y-m-d');
            $days[$day] = 0;
        }

        foreach ($results as $row) {
            $days[$row->_id] = $row->total_duration;
        }


        $data = [
            'series' => [
                [
                    'name' => 'Yayın Süresi',
                    'data' => array_values($days)
                ]
            ],
            'categories' => collect(array_keys($days))->map(function ($date) {
                return Carbon::parse($date)->locale('tr')->translatedFormat('l');
            })->toArray()
        ];

        return response()->json($data);
    }

    public function getHourChartData(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $startDate = Carbon::parse($request->input('start_date', '2025-01-01'))->startOfDay();
        $endDate = Carbon::parse($request->input('end_date', now()->format('Y-m-d')))->endOfDay();

        $pipeline = [
            [
                '$match' => [
                    'started_at' => [
                        '$gte' => new UTCDateTime($startDate),
                        '$lte' => new UTCDateTime($endDate),
                    ]
                ]
            ],
            [
                '$addFields' => [
                    'hour' => [
                        '$dateToString' => [
                            'format' => '%H',
                            'date' => '$started_at',
                            'timezone' => 'Europe/Istanbul'
                        ]
                    ]
                ]
            ],
            [
                '$group' => [
                    '_id' => '$hour',
                    'total_duration' => ['$sum' => '$duration']
                ]
            ],
            [
                '$sort' => ['_id' => 1]
            ]
        ];

        $results = AgoraChannel::raw(function ($collection) use ($pipeline) {
            return $collection->aggregate($pipeline);
        });

        // 00'dan 23'e kadar tüm saatleri sıfırla
        $hours = [];
        for ($i = 0; $i < 24; $i++) {
            $hour = str_pad($i, 2, '0', STR_PAD_LEFT);
            $hours[$hour] = 0;
        }

        foreach ($results as $row) {
            $hours[$row->_id] = $row->total_duration;
        }

        $data = [
            'series' => [
                [
                    'name' => 'Yayın Süresi',
                    'data' => array_values($hours)
                ]
            ],
            'categories' => collect(array_keys($hours))->map(fn($h) => "$h:00")->toArray()
        ];

        return response()->json($data);
    }

    public function getOpenStreamByTeamChartData(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $startDate = Carbon::parse($request->input('start_date', '2025-01-01'))->startOfDay();
        $endDate = Carbon::parse($request->input('end_date', now()->format('Y-m-d')))->endOfDay();


        $pipeline = [
            [
                '$match' => [
                    'started_at' => [
                        '$gte' => new UTCDateTime($startDate),
                        '$lte' => new UTCDateTime($endDate),
                    ]
                ]
            ],
            [
                '$group' => [
                    '_id' => '$team_id', // takım id’sine göre grupla
                    'total_duration' => ['$sum' => '$duration'] // saniye cinsinden yayın süresi
                ]
            ],
            [
                '$sort' => ['total_duration' => -1] // en çoktan en aza sırala
            ]
        ];

        $results = AgoraChannel::raw(function ($collection) use ($pipeline) {
            return $collection->aggregate($pipeline);
        });

        // İlk 4 takım ayrı, kalanlar "Diğer"e
        $otherTotal = 0;
        $labels = [];
        $data = [];

        $counter = 0;

        foreach ($results as $row) {
            $teamId = $row->_id;

            $teamName = Team::find($teamId)?->name ?? "Belirtilmemiş";

            if ($counter < 4) {
                $labels[] = $teamName;
                $data[] = $row->total_duration;
            } else {
                $otherTotal += $row->total_duration;
            }

            $counter++;
        }

        if ($otherTotal > 0) {
            $labels[] = 'Diğer';
            $data[] = $otherTotal;
        }

        $chartData = [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => [
                        '#004d00',
                        '#007a00',
                        '#00b300',
                        '#33cc33',
                        '#99ff99'
                    ]
                ]
            ],
            'labels' => $labels
        ];

        return response()->json($chartData);
    }

    public function getWatchersByTeamChartData(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $startDate = Carbon::parse($request->input('start_date', '2025-01-01'))->startOfDay();
        $endDate = Carbon::parse($request->input('end_date', now()->format('Y-m-d')))->endOfDay();

        $pipeline = [
            [
                '$match' => [
                    'joined_at' => [
                        '$gte' => new UTCDateTime($startDate),
                        '$lte' => new UTCDateTime($endDate),
                    ],
                    'user_data.primary_team_id' => ['$exists' => true, '$ne' => null],
                    'watch_duration' => ['$gt' => 0]
                ]
            ],
            [
                '$group' => [
                    '_id' => '$user_data.primary_team_id',
                    'total_duration' => ['$sum' => '$watch_duration']
                ]
            ],
            [
                '$sort' => ['total_duration' => -1]
            ]
        ];

        $results = AgoraChannelViewer::raw(function ($collection) use ($pipeline) {
            return $collection->aggregate($pipeline);
        });


        // İlk 4 takım ayrı, kalanlar "Diğer"e
        $otherTotal = 0;
        $labels = [];
        $data = [];

        $counter = 0;

        foreach ($results as $row) {
            $teamId = $row->id;

            $teamName = Team::find($teamId)?->name ?? "Belirtilmemiş";

            if ($counter < 4) {
                $labels[] = $teamName;
                $data[] = $row->total_duration;
            } else {
                $otherTotal += $row->total_duration;
            }

            $counter++;
        }

        if ($otherTotal > 0) {
            $labels[] = 'Diğer';
            $data[] = $otherTotal;
        }

        $chartData = [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => [
                        '#004d00',
                        '#007a00',
                        '#00b300',
                        '#33cc33',
                        '#99ff99'
                    ]
                ]
            ],
            'labels' => $labels
        ];

        return response()->json($chartData);
    }

    public function getTopGifts(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $startDate = Carbon::parse($request->input('start_date', '2025-01-01'))->startOfDay();
        $endDate = Carbon::parse($request->input('end_date', now()->format('Y-m-d')))->endOfDay();

        $data = AgoraChannel::select([
            'title',
            'channel_name',
            'total_coins_earned'
        ])
            ->whereBetween('started_at', [$startDate, $endDate])
            ->orderByDesc('total_coins_earned')
            ->limit(5)->get();

        return response()->json($data);
    }

    public function getGiftsChartData(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
        ]);

        if ($request->filled('start_date')) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = $startDate->copy()->addDays(6)->endOfDay(); // 6 gün sonrasını alıyoruz
        } else {
            $endDate = Carbon::now()->endOfDay();
            $startDate = $endDate->copy()->subDays(6)->startOfDay(); // 6 gün öncesinden bugüne
        }

        $pipeline = [
            [
                '$match' => [
                    'started_at' => [
                        '$gte' => new UTCDateTime($startDate),
                        '$lte' => new UTCDateTime($endDate)
                    ]
                ]
            ],
            [
                '$addFields' => [
                    'day' => [
                        '$dateToString' => [
                            'format' => '%Y-%m-%d',
                            'date' => '$started_at',
                            'timezone' => 'Europe/Istanbul'
                        ]
                    ]
                ]
            ],
            [
                '$group' => [
                    '_id' => '$day',
                    'total_coins' => ['$sum' => '$total_coins_earned']
                ]
            ],
            [
                '$sort' => ['_id' => 1]
            ]
        ];

        $results = AgoraChannel::raw(function ($collection) use ($pipeline) {
            return $collection->aggregate($pipeline);
        });

        // Son 7 günün tarihlerini al
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $day = $startDate->copy()->addDays($i)->format('Y-m-d');
            $days[$day] = 0;
        }

        foreach ($results as $row) {
            $days[$row->_id] = $row->total_coins;
        }

        $data = [
            'series' => [
                [
                    'name' => 'Shoot Coin',
                    'data' => array_values($days)
                ]
            ],
            'categories' => collect(array_keys($days))->map(function ($date) {
                return Carbon::parse($date)->locale('tr')->translatedFormat('l');
            })->toArray()
        ];

        return response()->json($data);
    }

    public function getWatchersChartData(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
        ]);

        if ($request->filled('start_date')) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = $startDate->copy()->addDays(6)->endOfDay(); // 6 gün sonrasını alıyoruz
        } else {
            $endDate = Carbon::now()->endOfDay();
            $startDate = $endDate->copy()->subDays(6)->startOfDay(); // 6 gün öncesinden bugüne
        }

        $pipeline = [
            [
                '$match' => [
                    'started_at' => [
                        '$gte' => new UTCDateTime($startDate),
                        '$lte' => new UTCDateTime($endDate)
                    ]
                ]
            ],
            [
                '$addFields' => [
                    'day' => [
                        '$dateToString' => [
                            'format' => '%Y-%m-%d',
                            'date' => '$started_at',
                            'timezone' => 'Europe/Istanbul'
                        ]
                    ]
                ]
            ],
            [
                '$group' => [
                    '_id' => '$day',
                    'total_viewers' => ['$sum' => '$viewer_count']
                ]
            ],
            [
                '$sort' => ['_id' => 1]
            ]
        ];

        $results = AgoraChannel::raw(function ($collection) use ($pipeline) {
            return $collection->aggregate($pipeline);
        });

        // Son 7 günün tarihlerini al
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $day = $startDate->copy()->addDays($i)->format('Y-m-d');
            $days[$day] = 0;
        }

        foreach ($results as $row) {
            $days[$row->_id] = $row->total_viewers;
        }

        $data = [
            'series' => [
                [
                    'name' => 'İzleyici Sayısı',
                    'data' => array_values($days)
                ]
            ],
            'categories' => collect(array_keys($days))->map(function ($date) {
                return Carbon::parse($date)->locale('tr')->translatedFormat('l');
            })->toArray()
        ];

        return response()->json($data);
    }
}
