<?php

namespace App\Http\Controllers\Admin\Reports\Users;

use Carbon\Carbon;
use App\Models\VideoLike;
use App\Models\VideoView;
use App\Models\VideoComment;
use Illuminate\Http\Request;
use MongoDB\BSON\UTCDateTime;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class EngagementMetricsController extends Controller
{
    public function index()
    {
        return view('admin.pages.reports.users.engagement-metrics.index');
    }

    public function getMetricsData(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $startDate = Carbon::parse($request->input('start_date', '2025-01-01'))->startOfDay();
        $endDate = Carbon::parse($request->input('end_date', now()->format('Y-m-d')))->endOfDay();

        $videoLikeMetrics = $this->getVideoLikeMetrics($startDate, $endDate);
        $videoCommentMetrics = $this->getVideoCommentMetrics($startDate, $endDate);
        $videoViewMetrics = $this->getVideoViewMetrics($startDate, $endDate);

        return response()->json([
            'sport_videos' => [
                'like_count' => $videoLikeMetrics['sport_videos_like_count'],
                'comment_count' => $videoCommentMetrics['sport_videos_comment_count'],
                'view_metrics' => $videoViewMetrics['sport'],
            ],
            'other_videos' => [
                'like_count' => $videoLikeMetrics['other_videos_like_count'],
                'comment_count' => $videoCommentMetrics['other_videos_comment_count'],
                'view_metrics' => $videoViewMetrics['other'],
            ],
        ]);
    }

    protected function getVideoLikeMetrics($startDate, $endDate): array
    {
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
            // Burada video.is_sport değerine göre filtreleme yapıyoruz
            [
                '$group' => [
                    '_id' => '$video.is_sport', // Burada video.is_sport'a göre gruplama yapıyoruz
                    'total_like' => ['$sum' => 1] // Her grup için beğeni sayısını topluyoruz
                ]
            ],
            [
                '$project' => [
                    '_id' => 0,
                    'is_sport' => '$_id', // video.is_sport'ı döndürmek için
                    'total_like' => 1
                ]
            ]
        ];

        $results = VideoLike::raw(function ($collection) use ($pipeline) {
            return $collection->aggregate($pipeline);
        });

        $sportVideosLikeCount = 0;
        $otherVideosLikeCount = 0;

        foreach ($results as $result) {
            if ($result['is_sport'] === true) {
                $sportVideosLikeCount = $result['total_like'];
            } else {
                $otherVideosLikeCount = $result['total_like'];
            }
        }

        return [
            'sport_videos_like_count' => $sportVideosLikeCount,
            'other_videos_like_count' => $otherVideosLikeCount,
        ];
    }
    protected function getVideoCommentMetrics($startDate, $endDate): array
    {
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
            // Burada video.is_sport değerine göre filtreleme yapıyoruz
            [
                '$group' => [
                    '_id' => '$video.is_sport', // Burada video.is_sport'a göre gruplama yapıyoruz
                    'total_like' => ['$sum' => 1] // Her grup için beğeni sayısını topluyoruz
                ]
            ],
            [
                '$project' => [
                    '_id' => 0,
                    'is_sport' => '$_id', // video.is_sport'ı döndürmek için
                    'total_like' => 1
                ]
            ]
        ];

        $results = VideoComment::raw(function ($collection) use ($pipeline) {
            return $collection->aggregate($pipeline);
        });

        $sportVideosLikeCount = 0;
        $otherVideosLikeCount = 0;

        foreach ($results as $result) {
            if ($result['is_sport'] === true) {
                $sportVideosLikeCount = $result['total_like'];
            } else {
                $otherVideosLikeCount = $result['total_like'];
            }
        }

        return [
            'sport_videos_comment_count' => $sportVideosLikeCount,
            'other_videos_comment_count' => $otherVideosLikeCount,
        ];
    }
    protected function getVideoViewMetrics($startDate, $endDate): array
    {
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
            [
                '$group' => [
                    '_id' => '$video.is_sport',
                    'total_views' => ['$sum' => 1],
                    'completed_views' => [
                        '$sum' => [
                            '$cond' => [
                                'if' => ['$eq' => ['$completed', true]],
                                'then' => 1,
                                'else' => 0
                            ]
                        ]
                    ],
                    'total_fair_impression' => [
                        '$sum' => [
                            '$cond' => [
                                'if' => ['$gt' => ['$duration_watched', 9]],
                                'then' => 1,
                                'else' => 0
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $results = VideoView::raw(function ($collection) use ($pipeline) {
            return $collection->aggregate($pipeline);
        });

        $data = [
            'sport' => [
                'total_views' => 0,
                'total_completed_views' => 0,
                'total_fair_impression' => 0,
                'completed_rate' => 0,
                'viewing_rate' => 0,
            ],
            'other' => [
                'total_views' => 0,
                'total_completed_views' => 0,
                'total_fair_impression' => 0,
                'completed_rate' => 0,
                'viewing_rate' => 0,
            ]
        ];

        foreach ($results as $result) {
            $totalViews = $result['total_views'] ?? 0;
            $totalCompletedViews = $result['completed_views'] ?? 0;
            $totalFairImpression = $result['total_fair_impression'] ?? 0;

            $completedRate = $totalViews > 0 ? round(($totalCompletedViews / $totalViews) * 100, 2) : 0;
            $viewingRate = $totalViews > 0 ? round(($totalFairImpression / $totalViews) * 100, 2) : 0;

            if ($result['id'] === true) { //is_sport ise
                $data['sport']['total_views'] = $totalViews;

                $data['sport']['total_completed_views'] = $totalCompletedViews;
                $data['sport']['total_fair_impression'] = $totalFairImpression;

                $data['sport']['completed_rate'] = $completedRate;
                $data['sport']['viewing_rate'] = $viewingRate;
            } else {
                $data['other']['total_views'] = $totalViews;
                $data['other']['total_completed_views'] = $totalCompletedViews;
                $data['other']['total_fair_impression'] = $totalFairImpression;
                $data['other']['completed_rate'] = $completedRate;
                $data['other']['viewing_rate'] = $viewingRate;
            }
        }

        return $data;
    }
}
