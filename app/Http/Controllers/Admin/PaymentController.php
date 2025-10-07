<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Morph\Payment;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Notifications\EftPaymentRejected;

class PaymentController extends Controller
{
    public function index()
    {
        return view('admin.pages.payments.index');
    }

    public function dataTable(Request $request): JsonResponse
    {
        $query = Payment::query();

        $recordsTotal = (clone $query)->count();

        // Search
        if (!empty($request->input('search')['value'])) {
            $search = $request->input('search')['value'];
            $query->where('transaction_id', 'ILIKE', "%{$search}%");
        }

        // Filters
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [$request->input('start_date') . ' 00:00:00', $request->input('end_date') . ' 23:59:59']);
        }
        if ($request->filled('channel_id')) {
            $query->where('channel_id', $request->input('channel_id'));
        }
        if ($request->filled('status_id')) {
            $query->where('status_id', $request->input('status_id'));
        }


        // Order by
        $columns = ['id', 'id', 'payable_type', 'total_amount', 'channel_id', 'status_id', 'id', 'created_at'];

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
            $status = "<span class='badge badge-secondary'>{$item->get_status}</span>";
            $failureReason = $item->failure_reason ?? '-';

            $payerType = "-";
            if ($item->user_id) {
                $payerType = "Kullanıcı";
            } elseif ($item->advertiser_id) {
                $payerType = "Reklam Veren";
            }

            $payerName = $item->user?->nickname ?? $item->advertiser?->name ?? '-';
            $payer = "<div>
                <div class='text-gray-800'>{$payerName}</div>
                <div class='text-gray-500 fw-semibold fs-8'>{$payerType}</div>
            </div>";


            $payableType = $item->get_payable_type ?? "Belirtilmemiş";
            return [
                $item->id,
                $payer,
                "<span class='badge badge-secondary'>{$payableType}</span>",
                $item->draw_total_amount,
                "<span class='badge badge-secondary'>{$item->get_channel}</span>",
                $status,
                "<span class='fs-7'>{$failureReason}<span>",
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

    public function waitingApproval()
    {
        return view('admin.pages.payments.waiting-approval.index');
    }

    public function waitingApprovalDataTable(Request $request): JsonResponse
    {
        $query = Payment::query()->where('status_id', Payment::STATUS_WAITING_FOR_APPROVAL);

        $recordsTotal = (clone $query)->count();

        // Search
        if (!empty($request->input('search')['value'])) {
            $search = $request->input('search')['value'];
            $query->where('transaction_id', 'ILIKE', "%{$search}%");
        }

        // Filters
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [$request->input('start_date') . ' 00:00:00', $request->input('end_date') . ' 23:59:59']);
        }


        // Order by
        $columns = ['id', 'total_amount', 'user_id', 'channel_id', 'status_id', 'created_at'];

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
            $status = "<span class='badge badge-secondary'>{$item->get_status}</span>";

            return [
                $item->id,
                $item->draw_total_amount,
                $item->user?->nickname ?? '-',
                "<span class='badge badge-secondary'>{$item->get_created_at}</span>",
                "<button class='btn btn-primary btn-sm me-1 approveBtn' data-id='{$item->id}'>Onayla</button><button class='btn btn-danger btn-sm rejectBtn' data-id='{$item->id}'>Reddet</button>"
            ];
        });

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function approve(Request $request, $id): JsonResponse
    {
        $payment = Payment::find($id);
        if (!$payment) {
            return response()->json([
                'message' => 'Ödeme bulunamadı.',
            ], 404);
        }

        if ($payment->status_id !== Payment::STATUS_WAITING_FOR_APPROVAL) {
            return response()->json([
                'message' => 'Ödeme durumu onaylanmak için uygun değil.',
            ], 404);
        }

        $payment->update([
            'status_id' => Payment::STATUS_COMPLETED,
            'paid_at' => Carbon::now()
        ]);

        if (method_exists($payment->payable, 'paymentCallbackTransactions')) {
            $payment->payable->paymentCallbackTransactions($payment);
        }

        return response()->json([
            'message' => 'Ödeme onaylandı.',
        ]);
    }

    public function reject(Request $request, $id): JsonResponse
    {
        $request->validate([
            'failure_reason' => 'nullable|string|max:255',
        ]);

        /** @var Payment $payment */
        $payment = Payment::find($id);
        if (!$payment) {
            return response()->json([
                'message' => 'Ödeme bulunamadı.',
            ], 404);
        }

        if ($payment->status_id !== Payment::STATUS_WAITING_FOR_APPROVAL) {
            return response()->json([
                'message' => 'Ödeme durumu reddedilmek için uygun değil.',
            ], 404);
        }

        $payment->update([
            'status_id' => Payment::STATUS_FAILED,
            'failure_reason' => $request->input('failure_reason'),
        ]);

        $payment->user?->notify(new EftPaymentRejected($payment));

        return response()->json([
            'message' => 'Ödeme reddedildi.',
        ]);
    }
}
