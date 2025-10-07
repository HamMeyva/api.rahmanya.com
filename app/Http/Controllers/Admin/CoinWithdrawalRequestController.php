<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Variable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Coin\CoinWithdrawalRequest;
use App\Models\Relations\UserCoinTransaction;
use App\Notifications\CoinWithdrawalRequestApproved;
use App\Notifications\CoinWithdrawalRequestRejected;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CoinWithdrawalRequestController extends Controller
{
    public function index()
    {
        return view('admin.pages.coin-withdrawal-requests.index');
    }

    public function dataTable(Request $request): JsonResponse
    {
        $query = CoinWithdrawalRequest::query();

        $recordsTotal = (clone $query)->count();

        // Search
        if (!empty($request->input('search')['value'])) {
            $search = $request->input('search')['value'];
            $query->where('amount', 'LIKE', "%{$search}%")
                ->orWhereHas('user', function ($q) use ($search) {
                    $q->where(DB::raw("CONCAT(name, ' ', surname)"), 'ILIKE', "%{$search}%");
                });
        }

        // Filters
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [$request->input('start_date') . ' 00:00:00', $request->input('end_date') . ' 23:59:59']);
        }
        if ($request->filled('status_id')) {
            $query->where('status_id', $request->input('status_id'));
        }


        // Order by
        $columns = ['id', 'user_id', 'coin_amount', 'unit_coin_price', 'total_coin_price', 'status_id', 'created_at'];

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
            $shootCoinImage = assetAdmin('images/shoot-coin.svg');
            $coinAmount = "<div class='text-gray-600 fw-bold d-flex align-items-center gap-2'>
                                    <img src='{$shootCoinImage}' alt='Shoot Coin'
                                        class='img img-fluid' width='24' height='24'>
                                    <div>
                                        {$item->coin_amount}
                                    </div>
                                </div>";
            $status = "<span class='badge badge-{$item->get_status_color}'>{$item->get_status}</span>";

            return [
                $item->id,
                $item->user->full_name,
                $coinAmount,
                $item->draw_coin_unit_price,
                $item->draw_coin_total_price,
                $status,
                "<span class='badge badge-secondary'>{$item->get_created_at}</span>",
                view('components.admin.action-buttons', [
                    'itemId' => $item->id,
                    'showEdit' => true,
                    'editUrl' => route('admin.coin-withdrawal-requests.show', ['id' => $item->id]),
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
        $withdrawalRequest = CoinWithdrawalRequest::find($id);
        if (!$withdrawalRequest) {
            throw new NotFoundHttpException();
        }

        $userProfileUrl = route('admin.users.show', $withdrawalRequest->user->id);

        return view('admin.pages.coin-withdrawal-requests.show', compact('withdrawalRequest', 'userProfileUrl'));
    }

    public function approve($id): JsonResponse
    {
        /** @var \App\Models\Coin\CoinWithdrawalRequest $coinWithdrawalRequest */
        $coinWithdrawalRequest = CoinWithdrawalRequest::find($id);
        if (!$coinWithdrawalRequest) {
            throw new NotFoundHttpException();
        }

        DB::beginTransaction();
        try {
            // 1. Talep durumunu güncelle
            $coinWithdrawalRequest->status_id = CoinWithdrawalRequest::STATUS_APPROVED;
            $coinWithdrawalRequest->approved_at = now();
            $coinWithdrawalRequest->save();

            // 2. Coin transactions'a işlem hareketini ekle
            UserCoinTransaction::create([
                'user_id' => $coinWithdrawalRequest->user_id,
                'amount' => $coinWithdrawalRequest->coin_amount,
                'wallet_type' => Variable::WALLET_TYPE_EARNED,
                'transaction_type' => UserCoinTransaction::TRANSACTION_TYPE_WITHDRAWAL,
            ]);

            // 3. Bildirim gönder
            $coinWithdrawalRequest->user->notify(new CoinWithdrawalRequestApproved($coinWithdrawalRequest));

            DB::commit();
            return response()->json([
                'message' => 'Talep onaylandı.',
                'redirect_url' => route('admin.coin-withdrawal-requests.index')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Coin withdrawal approval failed', [
                'coin_withdrawal_request_id' => $coinWithdrawalRequest->id,
                'exception' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Bir hata oluştu, işlem yapılmadı.'
            ], 500);
        }
    }

    public function reject(Request $request, $id): JsonResponse
    {
        $request->validate([
            'reject_reason' => 'nullable|string|max:500'
        ]);

        /** @var \App\Models\Coin\CoinWithdrawalRequest $coinWithdrawalRequest */ //ide uyarısı gitsin diye eklendi.
        $coinWithdrawalRequest = CoinWithdrawalRequest::find($id);
        if (!$coinWithdrawalRequest) {
            throw new NotFoundHttpException();
        }

        DB::beginTransaction();
        try {
            //1. talep durumunu güncelle
            $coinWithdrawalRequest->status_id = CoinWithdrawalRequest::STATUS_REJECTED;
            $coinWithdrawalRequest->reject_reason = $request->reject_reason;
            $coinWithdrawalRequest->rejected_at = now();
            $coinWithdrawalRequest->save();

            //2. bildirim gönder
            $coinWithdrawalRequest->user->notify(new CoinWithdrawalRequestRejected($coinWithdrawalRequest));

            DB::commit();
            return response()->json([
                'message' => 'Talep reddedildi.',
                'redirect_url' => route('admin.coin-withdrawal-requests.index')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Coin withdrawal rejection failed', [
                'coin_withdrawal_request_id' => $coinWithdrawalRequest->id,
                'exception' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Bir hata oluştu, işlem yapılmadı.'
            ], 500);
        }
    }
}
