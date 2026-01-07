<?php

namespace App\Http\Controllers\Admin;

use Exception;
use App\Models\Admin;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Helpers\CommonHelper;
use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;
use App\Models\Agora\AgoraChannel;
use App\Models\Morph\ReportProblem;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use App\Services\LiveStream\AgoraChannelService;
use App\Services\LiveStream\LiveStreamChatService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LiveStreamController extends Controller
{
    public function index(): View
    {
        return view('admin.pages.live-streams.index');
    }

    public function dataTable(Request $request): JsonResponse
    {
        $query = AgoraChannel::query();

        $recordsTotal = (clone $query)->count();

        // Search
        if (!empty($request->input('search')['value'])) {
            $search = $request->input('search')['value'];
            $query->where('title', 'LIKE', "%{$search}%");
        }

        // Filters
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = Carbon::parse($request->input('end_date'))->endOfDay();

            $query->whereBetween('started_at', [$startDate, $endDate]);
        }

        $recordsFiltered = (clone $query)->count();

        // Order by
        $columns = ['title', 'user_id', 'is_online', 'viewer_count', 'total_gifts', 'total_coins_earned', 'started_at', 'ended_at'];

        $orderColumnIndex = $request->input('order.0.column');
        $orderColumn = $columns[$orderColumnIndex] ?? 'started_at';
        $orderDir = $request->input('order.0.dir', 'desc');

        if ($orderColumnIndex === null) {
            $orderColumn = 'started_at';
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
            $showUrl = route('admin.live-streams.show', ['id' => $item->id]);

            $image = "<div class='symbol symbol-50px position-relative d-flex align-items-center'>
                <span class='symbol-label' style='background-image:url({$item->thumbnail_url});'></span>
            </div>";

            $limitedTitle = (new CommonHelper())->limitText($item->title);
            $limitedDesc = (new CommonHelper())->limitText($item->description);

            $column = "<a href='{$showUrl}' class='d-flex'>
                    {$image}
                    <div class='ms-5 d-flex flex-column justify-content-center'>
                        <span class='text-dark'>{$limitedTitle}</span>                 
                        <div class='text-muted fs-7 fw-bold'>{$item->channel_name}</div>
                        <div class='text-muted fs-7 fw-bold'>{$limitedDesc}</div>
                    </div>
                </a>";

            $user = $item->user();
            $userNickname = $user?->nickname ?? 'Bulunamadı';

            $status = $item->is_online ? 'Aktif' : 'Pasif';
            $statusBg = $item->is_online ? 'success' : 'danger';

            return [
                $column,
                "<span class='badge badge-secondary'>{$userNickname}</span>",
                "<span class='badge badge-{$statusBg}'>{$status}</span>",
                "<span class='badge badge-secondary'>{$item->viewer_count} Kişi</span>",
                "<span class='badge badge-secondary'>{$item->total_gifts} Adet</span>",
                "<span class='badge badge-secondary'>{$item->total_coins_earned} Coin</span>",
                "<span class='badge badge-secondary'>{$item->get_started_at}</span>",
                "<span class='badge badge-secondary'>{$item->get_ended_at}</span>",
                view('components.admin.action-buttons', [
                    'itemId' => $item->id,
                    'showView' => true,
                    'viewUrl' => $showUrl,
                    'showDelete' => false,
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

    public function show($id)
    {
        $stream = AgoraChannel::find($id);
        if (!$stream) {
            throw new NotFoundHttpException();
        }

        $user = $stream->user();

        $reportCount = Cache::remember("stream_report_count_{$stream->id}", 300, function () use ($stream) {
            return ReportProblem::query()
                ->where('entity_type', 'AgoraChannel')
                ->where('entity_id', $stream->id)
                ->count();
        });

        return view('admin.pages.live-streams.show', compact(['stream', 'user', 'reportCount']));
    }

    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:500',
            'description' => 'nullable|string|max:500',
        ]);

        $stream = AgoraChannel::withTrashed()->find($id);
        if (!$stream) {
            throw new NotFoundHttpException();
        }

        $stream->update([
            'title' => $request->input('title'),
            'description' => $request->input('description'),
        ]);

        return response()->json([
            'message' => "Yayın bilgileri başarıyla güncellendi.",
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $term = $request->term["term"] ?? '';

        $data = AgoraChannel::query()
            ->where(function ($query) use ($term) {
                $query->where('channel_name', 'LIKE', "%{$term}%");
            })
            ->limit(50)
            ->orderByDesc("created_at")
            ->get();

        $result = [];
        foreach ($data as $item) {
            $result[] = [
                "id" => $item->id,
                "name" => $item->channel_name,
                "extraParams" => $item
            ];
        }

        return response()->json([
            "items" => $result
        ]);
    }

    public function stop($id, AgoraChannelService $agoraChannelService): JsonResponse
    {
        // ✅ FIX: Try both _id and id for MongoDB compatibility
        $stream = AgoraChannel::withTrashed()->where('_id', $id)->first();

        if (!$stream) {
            // Try with id field as fallback
            $stream = AgoraChannel::withTrashed()->where('id', $id)->first();
        }

        if (!$stream) {
            return response()->json([
                'message' => "Yayın bulunamadı. ID: {$id}",
            ], 404);
        }

        try {
            $agoraChannelService->endStream($stream);
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();

            // ✅ FIX: If stream already closed, return 200 with message
            if (str_contains($errorMessage, 'zaten kapalı') || str_contains($errorMessage, 'already closed')) {
                return response()->json([
                    'message' => "Yayın zaten kapalı.",
                ], 200);
            }

            // Other errors return 500
            return response()->json([
                'message' => "Yayın durdurulurken hata: " . $errorMessage,
            ], 500);
        }

        return response()->json([
            'message' => "Yayın başarıyla durduruldu.",
        ]);
    }

    public function sendMessage(Request $request, $id, LiveStreamChatService $liveStreamChatService): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:200',
        ]);

        /** @var AgoraChannel $stream */
        $stream = AgoraChannel::withTrashed()->find($id);
        if (!$stream) {
            throw new NotFoundHttpException();
        }

        $message = "Yönetici Mesajı: " . $request->input('message');
        
        /** @var Admin $authUser */
        $authUser = $request->user();
        try {
            $liveStreamChatService->sendMessageByAdmin($stream, $authUser, $message);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 404);
        }

        return response()->json([
            'message' => "Mesaj başarıyla gönderildi.",
        ]);
    }
}
