<?php

namespace App\Http\Controllers\Admin\Reports\Users;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Video;
use App\Helpers\Variable;
use App\Models\VideoView;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Demographic\Gender;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\View\View;
use App\Http\Controllers\Controller;
use App\Models\Demographic\AgeRange;
use Illuminate\Support\Facades\Cache;

class DemographicsController extends Controller
{
    public function index(): View
    {
        return view('admin.pages.reports.users.demographics.index');
    }

    public function getAgeRangeChartData(Request $request): JsonResponse
    {
        $request->validate([
            'category' => 'required|in:all_users,sport_videos,other_videos,all_videos',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);
        $category = $request->input('category');

        $startDate = Carbon::parse($request->input('start_date', '2025-01-01'))->startOfDay();
        $endDate = Carbon::parse($request->input('end_date', now()->format('Y-m-d')))->endOfDay();

        $filterUserIds = $this->getUserIds($category);

        //---------------------
        $ageRanges = AgeRange::$ageRanges;
        $results = User::query()
            ->when($filterUserIds, function ($query) use ($filterUserIds) {
                return $query->whereIn('id', $filterUserIds);
            })
            ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                return $query->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->selectRaw("CASE
                WHEN age BETWEEN 1 AND 10 THEN " . AgeRange::AGE_1_10 . "
                WHEN age BETWEEN 11 AND 20 THEN " . AgeRange::AGE_11_20 . "
                WHEN age BETWEEN 21 AND 30 THEN " . AgeRange::AGE_21_30 . "
                WHEN age BETWEEN 31 AND 40 THEN " . AgeRange::AGE_31_40 . "
                WHEN age BETWEEN 41 AND 50 THEN " . AgeRange::AGE_41_50 . "
                WHEN age BETWEEN 51 AND 60 THEN " . AgeRange::AGE_51_60 . "
                WHEN age >= 61 THEN " . AgeRange::AGE_61_PLUS . "
                ELSE 0
            END as age_range,
            COUNT(*) as total
        ")
            ->groupBy('age_range')
            ->orderBy('age_range')
            ->get()
            ->pluck('total', 'age_range');

        $seriesData = [];

        foreach (array_keys($ageRanges) as $key) {
            $seriesData[] = $results[$key] ?? 0;
        }

        $data = [
            'series' => [
                [
                    'name' => 'Kullanıcı Sayısı',
                    'data' => $seriesData
                ]
            ],
            'categories' => array_values($ageRanges)
        ];

        return response()->json($data);
    }

    public function getGenderChartData(Request $request): JsonResponse
    {
        $request->validate([
            'category' => 'required|in:all_users,sport_videos,other_videos,all_videos',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);
        $category = $request->input('category');

        $startDate = Carbon::parse($request->input('start_date', '2025-01-01'))->startOfDay();
        $endDate = Carbon::parse($request->input('end_date', now()->format('Y-m-d')))->endOfDay();

        $filterUserIds = $this->getUserIds($category);

        //---------------------
        $results = User::query()
            ->when($filterUserIds, function ($query) use ($filterUserIds) {
                return $query->whereIn('id', $filterUserIds);
            })
            ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                return $query->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->selectRaw("CASE
                WHEN gender_id = '" . Gender::FEMALE . "' THEN " . Gender::FEMALE . "
                WHEN gender_id = '" . Gender::MALE . "' THEN " . Gender::MALE . "
                WHEN gender_id = '" . Gender::OTHER . "' THEN " . Gender::OTHER . "
                ELSE 0
                END as gender,
                COUNT(*) as total
            ")
            ->groupBy('gender_id')
            ->orderBy('gender_id')
            ->get()
            ->pluck('total', 'gender');

        return response()->json([
            'datasets' => [
                [
                    'data' => [
                        $results[Gender::FEMALE] ?? 0,
                        $results[Gender::MALE] ?? 0,
                        $results[Gender::OTHER] ?? 0,
                    ],
                    'backgroundColor' => ['#ff0083', '#00A3FF', '#E4E6EF']
                ]
            ],
            'labels' => ['Kadın', 'Erkek', 'Diğer'],
        ]);
    }

    public function getMapData(Request $request): JsonResponse
    {
        $request->validate([
            'category' => 'required|in:all_users,sport_videos,other_videos,all_videos',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);
        $category = $request->input('category');

        $startDate = Carbon::parse($request->input('start_date', '2025-01-01'))->startOfDay();
        $endDate = Carbon::parse($request->input('end_date', now()->format('Y-m-d')))->endOfDay();

        $userIds = $this->getUserIds($category);
        
        $usersQuery = User::query()
            ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                return $query->whereBetween('users.created_at', [$startDate, $endDate]);
            })
            ->when($userIds, fn($query) => $query->whereIn('users.id', $userIds))
            ->where('users.country_id', Variable::COUNTRY_ID_TURKEY);

        $totalCount = (clone $usersQuery)->count();

        $result = $usersQuery
            ->join('cities', 'users.city_id', '=', 'cities.id')
            ->select(
                'cities.code as city_code',
                'cities.name as city_name',
                DB::raw('count(*) as value')
            )
            ->groupBy('cities.code', 'cities.name')
            ->get();


        $data = $result->map(function ($item) {
            return [
                'id' => "TR-{$item->city_code}",
                'value' => $item->value,
                'name' => $item->city_name,
            ];
        });

        return response()->json([
            'data' => $data,
            'total_user_count' => $totalCount
        ]);
    }

    protected function getUserIds($category)
    {
        return Cache::remember("reports.users.demographics.user_ids_{$category}", 30, function () use ($category) {
            if (!in_array($category, ['sport_videos', 'other_videos', 'all_videos'])) {
                return null;
            }
    
            $sportVideoIds = Video::query()
                ->when($category === 'sport_videos', fn($q) => $q->where('is_sport', true))
                ->when($category === 'other_videos', fn($q) => $q->where('is_sport', false))
                ->pluck('id')
                ->toArray();
    
            $videoViews = VideoView::raw(function ($collection) use ($sportVideoIds) {
                return $collection->aggregate([
                    ['$match' => ['video_id' => ['$in' => $sportVideoIds]]],
                    ['$group' => ['_id' => '$user_id', 'viewCount' => ['$sum' => 1]]],
                    ['$sort' => ['viewCount' => -1]],
                    ['$limit' => 1000]
                ]);
            });
    
            return $videoViews->pluck('_id')->toArray();
        });
    }
}