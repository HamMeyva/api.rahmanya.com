<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\Challenge\Challenge;
use Illuminate\Contracts\View\View;
use Carbon\Carbon;
use App\Helpers\CommonHelper;

class ChallengeController extends Controller
{
    public function index(): View
    {
        return view('admin.pages.challenges.index');
    }

    public function dataTable(Request $request): JsonResponse
    {
        $query = Challenge::query();

        $recordsTotal = (clone $query)->count();

        // Search
        if (!empty($request->input('search')['value'])) {
            $search = $request->input('search')['value'];
            $query->where('title', 'LIKE', "%{$search}%");
        }

        // Filters
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = Carbon::parse($request->input('end_date'))->endOfDay();

            $query->whereBetween('started_at', [$startDate, $endDate]);
        }

        $recordsFiltered = (clone $query)->count();

        // Order by
        $columns = ['agora_channel_id', 'type_id', 'status_id', 'round_duration', 'max_coins', 'total_coins_earned', 'started_at', 'ended_at'];

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
            $stream = $item->agoraChannel;

            $streamImage = "<div class='symbol symbol-50px position-relative d-flex align-items-center'>
                <span class='symbol-label' style='background-image:url({$stream->thumbnail_url});'></span>
            </div>";

            $streamLimitedTitle = (new CommonHelper())->limitText($stream->title);
            $streamLimitedDesc = (new CommonHelper())->limitText($stream->description);

            $column = "<div class='d-flex'>
                    {$streamImage}
                    <div class='ms-5 d-flex flex-column justify-content-center'>
                        <span class='text-dark'>{$streamLimitedTitle}</span>                 
                        <div class='text-muted fs-7 fw-bold'>{$stream->channel_name}</div>
                        <div class='text-muted fs-7 fw-bold'>{$streamLimitedDesc}</div>
                    </div>
                </div>";

            return [
                $column,
                $item->get_type,
                "<span class='badge badge-{$item->get_status_color}'>{$item->get_status}</span>",
                "<span class='badge badge-secondary'>{$item->round_duration} saniye</span>",
                "<span class='badge badge-secondary'>{$item->max_coins} coin</span>",
                "<span class='badge badge-secondary'>{$item->total_coins_earned} coin</span>",
                "<span class='badge badge-secondary'>{$item->get_started_at}</span>",
                "<span class='badge badge-secondary'>{$item->get_ended_at}</span>",
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
