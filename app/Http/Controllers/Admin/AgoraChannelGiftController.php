<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Helpers\CommonHelper;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\Agora\AgoraChannelGift;

class AgoraChannelGiftController extends Controller
{
    public function index()
    {
        return view('admin.pages.agora-channel-gifts.index');
    }

    public function dataTable(Request $request): JsonResponse
    {
        $query = AgoraChannelGift::query();

        $recordsTotal = (clone $query)->count();

        // Search
        if (!empty($request->input('search')['value'])) {
            $search = $request->input('search')['value'];

            $query->where('gift_data.name', 'regexp', "/{$search}/i")
                ->orWhere('agora_channel_data.user_data.full_name', 'regexp', "/{$search}/i")
                ->orWhere('user_data.full_name', 'regexp', "/{$search}/i");
        }

        // Filters
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                Carbon::parse($request->input('start_date'))->startOfDay(),
                Carbon::parse($request->input('end_date'))->endOfDay(),
            ]);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }
        if ($request->filled('stream_id')) {
            $query->where('agora_channel_id', $request->input('stream_id'));
        }


        // Order by
        $columns = ['agora_channel_id', 'id', 'user_id', 'gift_data.name', 'id', 'created_at'];

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
            $giftData = $item->gift_data ?? [];
            $giftName = $giftData['name'] ?? 'Hediye Bulunamadı';
            $giftQuantity = $giftData['quantity'] ?? 1;
            $giftImageUrl = $giftData['image_path'] ?? '';
            $image = "<div class='symbol symbol-50px position-relative d-flex align-items-center'>
            <span class='symbol-label' style='background-image:url({$giftImageUrl});'></span>
            </div>";

            $limitedDesc = (new CommonHelper())->limitText($item->description);

            $column = "<div class='d-flex'>
                {$image}
                <div class='ms-5 d-flex flex-column justify-content-center'>
                    {$giftQuantity} x {$giftName}                            
                </div>
            </div>";


            $userData = $item->user_data ?? [];
            $userFullName = trim(($userData['nickname'] ?? ''));

            $agoraChannelData = $item->agora_channel_data ?? [];
            $streamerUserFullName = trim(($agoraChannelData['user_data']['nickname'] ?? ''));
         

            $createdAtHtml = "<span class='badge badge-secondary'>{$item->get_created_at}</span>";

            return [
                $column,
                "{$item->coin_value} Coin",
                $item->agora_channel_data['channel_name'] ?? 'Yayın Adı Bulunamadı',
                view('components.link', [
                    'label' => $streamerUserFullName,
                    'url' => route('admin.users.show', ['id' => $agoraChannelData['user_data']['id'] ?? null]),
                    'targetBlank' => true
                ])->render(),
                view('components.link', [
                    'label' => $userFullName,
                    'url' => route('admin.users.show', ['id' => $item->user_id ?? null]),
                    'targetBlank' => true
                ])->render(),
                $createdAtHtml,
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
