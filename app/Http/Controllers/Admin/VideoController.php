<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoLike;
use App\Models\VideoView;
use App\Models\VideoComment;
use Illuminate\Http\Request;
use App\Helpers\CommonHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\Morph\ReportProblem;
use Illuminate\Contracts\View\View;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use MongoDB\BSON\UTCDateTime;

class VideoController extends Controller
{
    public function index(): View
    {
        return view('admin.pages.videos.index');
    }

    public function dataTable(Request $request): JsonResponse
    {
        $query = Video::query();

        $recordsTotal = (clone $query)->count();

        // Search
        if (!empty($request->input('search')['value'])) {
            $search = $request->input('search')['value'];
            $query->where('id', 'LIKE', "%{$search}%")
                ->orWhere('video_guid', 'LIKE', "%{$search}%")
                ->orWhere('description', 'ILIKE', "%{$search}%");
        }

        // Filters
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->boolean('is_deleted')) {
            $query->onlyTrashed();
        }

        if ($request->boolean('is_sport')) {
            $query->where('is_sport', true);
        }


        // Order by
        if ($request->boolean('order_by_new_users')) {
            $newUserIds = User::query()
                ->orderBy('created_at', 'desc')
                ->limit(100)
                ->pluck('id')
                ->toArray();

            $query->whereIn('user_id', $newUserIds);
        }

        $columns = ['id', 'id', 'id', 'id', 'likes_count', 'comments_count', 'views_count', 'report_count', 'is_private', 'is_commentable', 'is_featured', 'created_at', 'updated_at'];

        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderColumn = $columns[$orderColumnIndex] ?? 'created_at'; // default order
        $orderDir = $request->input('order.0.dir', 'desc');

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
            $checkbox = "<input type='checkbox' class='form-check-input video-checkbox' data-video-id='{$item->id}'>";

            $thumbnail = "<div class='symbol symbol-75px'>
                <span class='symbol-label' style='background-image:url({$item->thumbnail_url});'></span>
            </div>";

            $user = $item->user();

            $userId = "<span class='badge badge-secondary'>{$user?->id}</span>";
            $collectionUuid = "<span class='badge badge-secondary'>{$item?->collection_uuid}</span>";
            $videoGuid = "<span class='badge badge-secondary'>{$item->video_guid}</span>";

            $privateVideo = $item->is_private ? 'check text-success' : 'xmark text-danger';
            $commentableVideo = $item->is_commentable ? 'check text-success' : 'xmark text-danger';
            $featuredVideo = $item->is_featured ? 'check text-success' : 'xmark text-danger';


            return [
                $checkbox,
                view('components.link', [
                    'label' => $thumbnail,
                    'url' => route('admin.videos.show', ['id' => $item->id])
                ])->render(),
                "{$userId}<br>{$collectionUuid}<br>{$videoGuid}",
                (new CommonHelper)->limitText($item->description),
                view('components.link', [
                    'label' => "{$user?->full_name}<br>{$user?->nickname}",
                    'url' => route('admin.users.show', ['id' => $item->user_id])
                ])->render(),
                "<span class='badge badge-secondary'>$item->likes_count</span>",
                "<span class='badge badge-secondary'>$item->comments_count</span>",
                "<span class='badge badge-secondary'>$item->views_count</span>",
                "<span class='badge badge-secondary'>$item->report_count</span>",
                "<i class='fa fa-{$privateVideo}'></i>",
                "<i class='fa fa-{$commentableVideo}'></i>",
                "<i class='fa fa-{$featuredVideo}'></i>",
                "<span class='badge badge-secondary'>{$item->get_created_at}</span>",
                "<span class='badge badge-secondary'>{$item->get_updated_at}</span>",
                view('components.admin.action-buttons', [
                    'itemId' => $item->id,
                    'showView' => true,
                    'viewUrl' => route('admin.videos.show', ['id' => $item->id]),
                    'showDelete' => true
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
        $video = Video::withTrashed()->find($id);
        if (!$video) {
            throw new NotFoundHttpException();
        }
        $user = $video->user();

        return view('admin.pages.videos.show', compact(['video', 'user']));
    }
    public function destroy(string $id): JsonResponse
    {
        $video = Video::find($id);
        if (!$video) {
            return response()->json([
                'message' => "Video bulunamadı.",
            ], 404);
        }

        $video->delete();

        return response()->json([
            'message' => "Video başarıyla silindi.",
        ]);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $request->validate([
            'video_ids' => 'required|array',
            'video_ids.*' => 'required|string'
        ]);

        $videoIds = $request->input('video_ids');
        $deletedCount = 0;

        foreach ($videoIds as $id) {
            $video = Video::find($id);
            if ($video) {
                $video->delete();
                $deletedCount++;
            }
        }

        return response()->json([
            'message' => "{$deletedCount} video başarıyla silindi.",
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'description' => 'required|string|max:500',
            'is_private' => 'nullable|boolean',
            'is_commentable' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'is_sport' => 'nullable|boolean',
        ]);

        $video = Video::withTrashed()->find($id);
        if (!$video) {
            throw new NotFoundHttpException();
        }

        $video->update([
            'description' => $request->input('description'),
            'is_private' => $request->has('is_private') ? true : false,
            'is_commentable' => $request->has('is_commentable') ? true : false,
            'is_featured' => $request->has('is_featured') ? true : false,
            'is_sport' => $request->has('is_sport') ? true : false,
        ]);

        return response()->json([
            'message' => "Video başarıyla güncellendi.",
        ]);
    }

    public function getLikeCommentChartData($id): JsonResponse
    {
        $video = Video::withTrashed()->find($id);
        if (!$video) {
            throw new NotFoundHttpException();
        }

        $startDate = Carbon::now()->subDays(6)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $pipeline = [
            [
                '$match' => [
                    'video_id' => $video->id,
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

        $likeResults = VideoLike::raw(function ($collection) use ($pipeline) {
            return $collection->aggregate($pipeline);
        });

        $commentResults = VideoComment::raw(function ($collection) use ($pipeline) {
            return $collection->aggregate($pipeline);
        });

        // Son 7 günü oluştur
        $days = collect();
        for ($i = 0; $i < 7; $i++) {
            $days->push(Carbon::now()->subDays(6 - $i)->format('Y-m-d'));
        }

        $likeMap = collect($likeResults)->mapWithKeys(fn($item) => [$item->_id => $item->count]);
        $commentMap = collect($commentResults)->mapWithKeys(fn($item) => [$item->_id => $item->count]);

        $likeData = [];
        $commentData = [];
        $categories = [];

        foreach ($days as $day) {
            $likeData[] = $likeMap->get($day, 0);
            $commentData[] = $commentMap->get($day, 0);
            $categories[] = Carbon::parse($day)->locale('tr')->isoFormat('dddd');
        }

        return response()->json([
            'series' => [
                [
                    'name' => 'Beğeni',
                    'data' => $likeData
                ],
                [
                    'name' => 'Yorum',
                    'data' => $commentData
                ]
            ],
            'categories' => $categories
        ]);
    }

    /* start:Video Views */
    public function viewsDataTable(Request $request, $id): JsonResponse
    {
        $query = VideoView::query()->where('video_id', $id);

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
            $userData = $item->user_data;

            $completed = $item->completed ? '<i class="fa fa-check"></i>' : '<i class="fa fa-times"></i>';
            return [
                view('components.link', [
                    'label' => $userData['nickname'] ?? '-',
                    'url' => isset($userData['id']) ? route('admin.users.show', ['id' => $userData['id']]) : "#",
                    'targetBlank' => true
                ])->render(),
                "<span class='badge badge-secondary'>{$item->duration_watched}</span>",
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
    /* end:Video Views*/

    /* start:Video Likes */
    public function likesDataTable(Request $request, $id): JsonResponse
    {
        $query = VideoLike::query()->where('video_id', $id);

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
        $columns = ['id', 'id', 'created_at'];

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
            $user = $item->user();
            return [
                $user?->nickname ?? '-',
                $user?->full_name ?? '-',
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
    /* end:Video Likes*/

    /* start:Video Comments */
    public function commentsDataTable(Request $request, $id): JsonResponse
    {
        $query = VideoComment::query()->where('video_id', $id);

        $recordsTotal = (clone $query)->count();

        // Search
        if (!empty($request->input('search')['value'])) {
            $search = $request->input('search')['value'];

            // start:user search
            $userIds = User::where('nickname', 'ILIKE', "%$search%")
                ->orWhere(DB::raw("CONCAT(name, ' ', surname)"), 'ILIKE', "%{$search}%")
                ->pluck('id')
                ->toArray();
            // end:user search

            $query->where(function ($q) use ($userIds, $search) {
                $q->whereIn('user_id', $userIds)
                    ->orWhere('comment', 'like', "%$search%");
            });
        }

        // Filters
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        $recordsFiltered = (clone $query)->count();

        // Order by
        $columns = ['id', 'id', 'likes_count', 'dislikes_count', 'replies_count', 'created_at'];

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
            $user = $item->user();
            return [
                view('components.link', [
                    'label' => $user?->nickname,
                    'url' => $user?->id ? route('admin.users.show', ['id' => $user?->id]) : "#"
                ])->render(),
                $item->comment,
                $item->likes_count,
                $item->dislikes_count,
                $item->replies_count,
                "<span class='badge badge-secondary'>{$item->get_created_at}</span>",
                view('components.admin.action-buttons', [
                    'itemId' => $item->id,
                    'showDelete' => true
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
    /* end:Video Comments*/

    /* start:Report Problems*/
    public function reportProblemsDataTable(Request $request, $id): JsonResponse
    {
        $query = ReportProblem::query()->where('entity_type', 'Video')->where('entity_id', $id);

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
    /* end:Report Problems*/
}
