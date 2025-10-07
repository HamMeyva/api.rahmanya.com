<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Story;
use App\Models\StoryLike;
use App\Models\StoryView;
use Illuminate\Http\Request;
use App\Helpers\CommonHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\Morph\ReportProblem;
use Illuminate\Contracts\View\View;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use MongoDB\BSON\UTCDateTime;

class StoryController extends Controller
{
    public function index()
    {
        return view('admin.pages.stories.index');
    }

    public function dataTable(Request $request)
    {
        $query = Story::query();

        $recordsTotal = (clone $query)->count();

        // Search
        if (!empty($request->input('search')['value'])) {
            $search = $request->input('search')['value'];
            $query->where('id', 'LIKE', "%{$search}%");
        }

        // Filters
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->boolean('is_deleted')) {
            $query->onlyTrashed();
        }

        // Order by
        $columns = ['id', 'user_id', 'views_count', 'likes_count', 'is_private', 'location', 'created_at', 'updated_at'];

        $orderColumnIndex = $request->input('order.0.column');
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';
        $orderDir = $request->input('order.0.dir', 'desc');

        if ($orderColumnIndex === null) {
            $orderColumn = 'id';
            $orderDir = 'desc';
        }

        $query->orderBy($orderColumn, $orderDir);

        //
        $recordsFiltered = (clone $query)->count();

        // Pagination
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $query->offset($start)->limit($length > 0 ? $length : PHP_INT_MAX);

        //
        $list = $query->get();

        $data = $list->map(function ($item) {
            $thumbnail = "<div class='symbol symbol-75px'>
                <span class='symbol-label' style='background-image:url({$item->thumbnailUrl});'></span>
            </div>";

            $user = $item->user();

            $privateVideo = $item->is_private ? '<i class="fa fa-check text-success"></i>' : '<i class="fa fa-times text-danger"></i>';

            return [
                view('components.link', [
                    'label' => $thumbnail,
                    'url' => route('admin.stories.show', ['id' => $item->id])
                ])->render(),
                view('components.link', [
                    'label' => $user?->full_name,
                    'url' => route('admin.users.show', ['id' => $user?->id])
                ])->render(),
                "<span class='badge badge-secondary'>$item->views_count</span>",
                "<span class='badge badge-secondary'>$item->likes_count</span>",
                $privateVideo,
                $item->location,
                $item->get_created_at,
                $item->get_updated_at,
                view('components.admin.action-buttons', [
                    'itemId' => $item->id,
                    'showView' => true,
                    'viewUrl' => route('admin.stories.show', ['id' => $item->id]),
                ])->render()
            ];
        });

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function show($id): View
    {
        $story = Story::withTrashed()->find($id);
        if (!$story) {
            throw new NotFoundHttpException();
        }
        $user = $story->user();
        return view('admin.pages.stories.show', compact(['story', 'user']));
    }
    
    public function destroy(string $id): JsonResponse
    {
        $story = Story::find($id);
        if (!$story) {
            return response()->json([
                'message' => "Hikaye bulunamadı.",
            ], 404);
        }

        $story->delete();

        return response()->json([
            'message' => "Hikaye başarıyla silindi.",
        ]);
    }

    public function getViewLikeChartData($id): JsonResponse
    {
        $story = Story::withTrashed()->find($id);
        if (!$story) {
            throw new NotFoundHttpException();
        }

        $startDate = Carbon::now()->subDays(6)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $pipeline = [
            [
                '$match' => [
                    'story_id' => $story->id,
                    'created_at' => [
                        '$gte' => new UTCDateTime($startDate),
                        '$lte' => new UTCDateTime($endDate),
                    ]
                ]
            ],
            [
                '$group' => [
                    '_id' => [
                        '$dateToString' => ['format' => '%Y-%m-%d', 'date' => '$created_at']
                    ],
                    'count' => ['$sum' => 1]
                ]
            ],
            [
                '$sort' => ['_id' => 1]
            ]
        ];

        $viewResults = StoryView::raw(function ($collection) use ($pipeline) {
            return $collection->aggregate($pipeline);
        });

        $likeResults = StoryLike::raw(function ($collection) use ($pipeline) {
            return $collection->aggregate($pipeline);
        });

        // Son 7 günü oluştur
        $days = collect();
        for ($i = 0; $i < 7; $i++) {
            $days->push(Carbon::now()->subDays(6 - $i)->format('Y-m-d'));
        }

        $viewMap = collect($viewResults)->mapWithKeys(fn($item) => [$item->_id => $item->count]);
        $likeMap = collect($likeResults)->mapWithKeys(fn($item) => [$item->_id => $item->count]);

        $viewData = [];
        $likeData = [];
        $categories = [];

        foreach ($days as $day) {
            $viewData[] = $viewMap->get($day, 0);
            $likeData[] = $likeMap->get($day, 0);
            $categories[] = Carbon::parse($day)->locale('tr')->isoFormat('dddd');
        }

        return response()->json([
            'series' => [
                [
                    'name' => 'Görüntüleme',
                    'data' => $viewData
                ],
                [
                    'name' => 'Beğeni',
                    'data' => $likeData
                ],
            ],
            'categories' => $categories
        ]);
    }

    public function likesDataTable(Request $request, $id): JsonResponse
    {
        $query = StoryLike::query()->where('story_id', $id);

        $recordsTotal = (clone $query)->count();

        // Search
        if (!empty($request->input('search')['value'])) {
            $search = $request->input('search')['value'];

            // start:user search
            $userIds = User::where('nickname', 'ILIKE', "%$search%")
                ->orWhere(DB::raw("CONCAT(name, ' ', surname)"), 'ILIKE', "%{$search}%")
                ->pluck('id')
                ->toArray();
            $query->whereIn('user_id', $userIds);
            // end:user search
        }

        // Filters
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        $recordsFiltered = (clone $query)->count();

        // Order by
        $columns = ['id', 'user_id'];

        $orderColumnIndex = $request->input('order.0.column');
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';
        $orderDir = $request->input('order.0.dir', 'desc');

        if ($orderColumnIndex === null) {
            $orderColumn = 'id';
            $orderDir = 'desc';
        }

        $query->orderBy($orderColumn, $orderDir);

        // Pagination
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $query->offset($start)->limit($length > 0 ? $length : PHP_INT_MAX);

        //
        $list = $query->get();

        $data = $list->map(function ($item) {
            $userData = $item->user_data;
            return [
                $userData['nickname'] ?? '-',
                isset($userData['name']) && $userData['surname'] ? $userData['name'] . ' ' . $userData['surname'] : '-',
                "<span class='badge badge-secondary'>{$item->get_created_at}</span>",
            ];
        });

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function viewsDataTable(Request $request, $id): JsonResponse
    {
        $query = StoryView::query()->where('story_id', $id);

        $recordsTotal = (clone $query)->count();

        // Search
        if (!empty($request->input('search')['value'])) {
            $search = $request->input('search')['value'];
            $query->where('id', 'LIKE', "%{$search}%")
                ->orWhere('ip_address', 'LIKE', "%{$search}%")
                ->orWhere('user_agent', 'LIKE', "%{$search}%");
        }

        // Filters
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        $recordsFiltered = (clone $query)->count();

        // Order by
        $columns = ['user_id', 'view_duration', 'completed', 'ip_address', 'user_agent', 'created_at'];

        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderColumn = $columns[$orderColumnIndex] ?? 'created_at';
        $orderDir = $request->input('order.0.dir', 'desc');

        $query->orderBy($orderColumn, $orderDir);

        // Pagination
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $query->offset($start)->limit($length > 0 ? $length : PHP_INT_MAX);

        //
        $list = $query->get();

        $data = $list->map(function ($item) {
            $userData = $item->user_data ?? [];

            $completed = $item->completed ? '<i class="fa fa-check"></i>' : '<i class="fa fa-times"></i>';
            return [
                view('components.link', [
                    'label' => $userData['nickname'] ?? 'Bulunamadı',
                    'url' => isset($userData['id']) ? route('admin.users.show', ['id' => $userData['id']]) : "#",
                    'targetBlank' => true
                ])->render(),
                "<span class='badge badge-secondary'>{$item->view_duration}</span>",
                $completed,
                "<span class='badge badge-secondary'>{$item->ip_address}</span>",
                "<span class='badge badge-secondary'>{$item->user_agent}</span>",
                "<span class='badge badge-secondary'>{$item->get_created_at}</span>",
            ];
        });

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function reportProblemsDataTable(Request $request, $id): JsonResponse
    {
        $query = ReportProblem::query()->where('entity_type', 'Story')->where('entity_id', $id);

        $recordsTotal = (clone $query)->count();

        if (!empty($request->input('search')['value'])) {
            $search = $request->input('search')['value'];
            $query->where('message', 'ILIKE', "%{$search}%");
        }

        // Filters
        if ($request->filled('report_problem_category_id')) {
            $query->where('report_problem_category_id', $request->input('report_problem_category_id'));
        }

        if ($request->filled('status_id')) {
            $query->where('status_id', $request->input('status_id'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        $recordsFiltered = (clone $query)->count();

        // Order by
        $columns = ['user_id', 'status_id', 'report_problem_category_id', 'message'];

        $orderColumnIndex = $request->input('order.0.column');
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';
        $orderDir = $request->input('order.0.dir', 'desc');

        if ($orderColumnIndex === null) {
            $orderColumn = 'id';
            $orderDir = 'desc';
        }

        $query->orderBy($orderColumn, $orderDir);

        // Pagination
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $query->offset($start)->limit($length > 0 ? $length : PHP_INT_MAX);

        //
        $list = $query->get();

        $data = $list->map(function ($item) {
            $statusColor = ReportProblem::$statusColors[$item->status_id];
            $status = "<span class='badge text-white' style='background-color:{$statusColor}'>{$item->get_status}</span>";
            return [
                $item->user?->nickname ?? '-',
                $status,
                $item->report_problem_category->name,
                (new CommonHelper())->limitText($item->message),
                view('components.admin.action-buttons', [
                    'itemId' => $item->id,
                    'showView' => true,
                    'viewUrl' => route('admin.report-problems.show', ['id' => $item->id]),
                ])->render()
            ];
        });

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }
}
