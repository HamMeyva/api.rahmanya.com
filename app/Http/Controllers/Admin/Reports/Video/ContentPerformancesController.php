<?php

namespace App\Http\Controllers\Admin\Reports\Video;

use Carbon\Carbon;
use App\Models\Video;
use App\Models\VideoView;
use Illuminate\Http\Request;

use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;
use App\Http\Controllers\Controller;
use MongoDB\BSON\UTCDateTime;

class ContentPerformancesController extends Controller
{
    public function index(): View
    {
        return view('admin.pages.reports.videos.content-performances.index');
    }

    public function getCompletedViewsData(Request $request): JsonResponse
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
                    'created_at' => [
                        '$gte' => new UTCDateTime($startDate),
                        '$lte' => new UTCDateTime($endDate),
                    ]
                ]
            ],
            [
                '$group' => [
                    '_id' => null,
                    'total_views' => ['$sum' => 1],
                    'completed_views' => [
                        '$sum' => [
                            '$cond' => [
                                'if' => ['$eq' => ['$completed', true]],
                                'then' => 1,
                                'else' => 0
                            ]
                        ]
                    ]
                ]
            ],
            [
                '$project' => [
                    '_id' => 0,
                    'total_views' => 1,
                    'completed_views' => 1
                ]
            ]
        ];

        $results = VideoView::raw(function ($collection) use ($pipeline) {
            return $collection->aggregate($pipeline);
        });

        $data = [
            'total_views' => $results[0]['total_views'] ?? 0,
            'completed_views' => $results[0]['completed_views'] ?? 0,
            'completion_rate' => 0,
        ];
        $data['completion_rate'] = ($data['completed_views'] > 0 && $data['total_views'] > 0) ? ($data['completed_views'] / $data['total_views']) * 100 : 0;

        return response()->json($data);
    }

    public function getMostViewsVideosData(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $startDate = Carbon::parse($request->input('start_date', '2025-01-01'))->startOfDay();
        $endDate = Carbon::parse($request->input('end_date', now()->format('Y-m-d')))->endOfDay();


        $videos = Video::query()->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('views_count', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'html' => view('admin.pages.reports.videos.content-performances.components.most-views-videos', compact('videos'))->render(),
        ]);
    }
}
