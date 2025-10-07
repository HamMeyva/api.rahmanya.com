<?php

namespace App\Http\Controllers\Admin\Reports\Users;

use Carbon\Carbon;
use App\Models\User;
use App\Models\VideoView;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Helpers\CommonHelper;
use MongoDB\BSON\UTCDateTime;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class VideoViewDurationsController extends Controller
{
    public function index(): View
    {
        return view('admin.pages.reports.users.video-view-durations.index');
    }

    public function getStats(Request $request): JsonResponse
    {
        $category = $request->input('category', 'all');

        $category = $request->input('category');
        $start = Carbon::now()->subMonths(11)->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        $pipeline = [
            [
                '$addFields' => [
                    'video_id' => ['$toObjectId' => '$video_id']
                ]
            ],
            [
                '$lookup' => [
                    'from' => 'videos',
                    'localField' => 'video_id',
                    'foreignField' => '_id',
                    'as' => 'video'
                ]
            ],
            [
                '$unwind' => '$video'
            ],
        ];

        // 🔀 Kategori filtresi (opsiyonel)
        if ($category === 'sport') {
            $pipeline[] = [
                '$match' => [
                    'video.is_sport' => true
                ]
            ];
        } elseif ($category === 'other') {
            $pipeline[] = [
                '$match' => [
                    'video.is_sport' => false
                ]
            ];
        }

        $pipeline[] = [
            '$group' => [
                '_id' => null,
                'total_duration' => ['$sum' => '$duration_watched'],
                'unique_users' => ['$addToSet' => '$user_id']
            ]
        ];

        $pipeline[] = [
            '$project' => [
                'total_duration' => 1,
                'unique_users_count' => ['$size' => '$unique_users'],
                '_id' => 0
            ]
        ];

        $results = VideoView::raw(function ($collection) use ($pipeline) {
            return $collection->aggregate($pipeline);
        });

        return response()->json([
            'total_view_duration' => (new CommonHelper)->formatToHourMinute($results[0]['total_duration'], 1),
            'unique_viewer_count' => $results[0]['unique_users_count'],
        ]);
    }
    public function chartData(Request $request): JsonResponse
    {
        $request->validate([
            'category' => 'required|in:all,sport,other',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $category = $request->input('category');
        $start = Carbon::now()->subMonths(11)->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        $pipeline = [
            [
                '$match' => [
                    'viewed_at' => [
                        '$gte' => new UTCDateTime($start),
                        '$lte' => new UTCDateTime($end),
                    ]
                ]
            ],
            [
                '$addFields' => [
                    'video_id' => ['$toObjectId' => '$video_id']
                ]
            ],
            [
                '$lookup' => [
                    'from' => 'videos',
                    'localField' => 'video_id',
                    'foreignField' => '_id',
                    'as' => 'video'
                ]
            ],
            [
                '$unwind' => '$video'
            ],
        ];

        // 🔀 Kategoriye göre filtre ekle
        if ($category === 'sport') {
            $pipeline[] = [
                '$match' => [
                    'video.is_sport' => true
                ]
            ];
        } elseif ($category === 'other') {
            $pipeline[] = [
                '$match' => [
                    'video.is_sport' => false
                ]
            ];
        }
        // all için herhangi bir filtre eklenmiyor

        // 💡 Grouping ve devamı
        $pipeline[] = [
            '$group' => [
                '_id' => [
                    'year' => ['$year' => '$viewed_at'],
                    'month' => ['$month' => '$viewed_at'],
                ],
                'total_duration' => ['$sum' => '$duration_watched'],
                'unique_users' => ['$addToSet' => '$user_id']
            ]
        ];

        $pipeline[] = [
            '$project' => [
                'month' => '$_id.month',
                'year' => '$_id.year',
                'total_duration' => 1,
                'unique_users_count' => ['$size' => '$unique_users'],
                '_id' => 0
            ]
        ];

        $pipeline[] = [
            '$sort' => [
                'year' => 1,
                'month' => 1
            ]
        ];

        $results = VideoView::raw(function ($collection) use ($pipeline) {
            return $collection->aggregate($pipeline);
        });

        // Veri mapleme (eksik ayları da 0 olarak ekliyoruz)
        $monthlyData = collect($results)->keyBy(function ($item) {
            return $item['year'] . '-' . str_pad($item['month'], 2, '0', STR_PAD_LEFT);
        });

        $labels = [];
        $data = [];

        for ($i = 0; $i < 12; $i++) {
            $date = $start->copy()->addMonths($i);
            $key = $date->format('Y-m');
            $monthName = $date->translatedFormat('M');

            $labels[] = $monthName;
            $data[] = isset($monthlyData[$key]) ? $monthlyData[$key]['total_duration'] : 0;
        }

        return response()->json([
            'total_duration' => (new CommonHelper)->formatToHourMinute($monthlyData->sum('total_duration'), 1),
            'unique_users_count' => $monthlyData->sum('unique_users_count'),
            'series' => [
                [
                    'name' => 'İzlenme Süresi',
                    'data' => $data
                ]
            ],
            'categories' => $labels
        ]);
    }

    public function getTopViewerUsers(Request $request): JsonResponse
    {
        $request->validate([
            'category' => 'required|in:all,sport,other',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $category = $request->input('category');
        
        $pipeline = [
            [
                '$group' => [
                    '_id' => '$user_id',
                    'total_duration' => ['$sum' => '$duration_watched']
                ]
            ],
            [
                '$sort' => [
                    'total_duration' => -1
                ]
            ],
            [
                '$limit' => 5
            ],
            [
                '$project' => [
                    'user_id' => '$_id',
                    'total_duration' => 1,
                    '_id' => 0
                ]
            ]
        ];
        $topUsers = VideoView::raw(function ($collection) use ($pipeline) {
            return $collection->aggregate($pipeline);
        });

        $data = collect($topUsers)->map(function ($item) {
            $user = User::select(['id', 'name', 'surname', 'nickname', 'email'])->find($item['user_id']);
            if (!$user) return null;

            return [
                'user' => [
                    'full_name' => $user->full_name,
                    'nickname' => $user->nickname,
                    'email' => $user->email,
                    'redirect_url' => route('admin.users.show', $user->id)
                ],
                'total_duration' => (new CommonHelper)->formatToHourMinute($item['total_duration'])
            ];
        })->filter();

        return response()->json([
            'items' => $data
        ]);
    }
}
