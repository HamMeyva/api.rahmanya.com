<?php

namespace App\Http\Controllers\Api\v1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Morph\Payment;
use App\Services\Payments\Gateways\IyzicoGateway;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function threedCallback(Request $request): void
    {
        $iyzicoPaymentId = $request->input('paymentId');
        $transactionId = $request->input('conversationId');
        $conversationData = $request->input('conversationData');
        $mdStatus = $request->input('mdStatus');
        $status = $request->input('status');

        $payment = Payment::query()
            ->where('transaction_id', $transactionId)
            ->first();
        if (!$payment) {
            Log::info('ThreedCallback - Payment not found', ['request' => $request->all()]);
            return;
        }

        $payment->update([
            'iyzico_payment_id' => $iyzicoPaymentId,
            'conversation_data' => $conversationData,
        ]);

        if ($status === 'success' && $mdStatus === '1' && $iyzicoPaymentId) {
            $paymentService = new IyzicoGateway();
            $threedsPaymentRequest = $paymentService->checkThreedsPaymentRequest($transactionId, $iyzicoPaymentId, $conversationData);
            $threedsPayment = $paymentService->threedsPayment($threedsPaymentRequest);

            if ($threedsPayment->getStatus() === 'success' && $threedsPayment->getPaymentId() === $iyzicoPaymentId) {
                $payment->update([
                    'status_id' => Payment::STATUS_COMPLETED,
                    'paid_at' => Carbon::now()
                ]);

                if (method_exists($payment->payable, 'paymentCallbackTransactions')) {
                    $payment->payable->paymentCallbackTransactions($payment);
                }
            }else{
                Log::info('CheckThreedsPaymentRequest  - Failed Payment', ['request' => $request->all()]);
                $payment->update([
                    'status_id' => Payment::STATUS_3D_FAILED,
                    'failure_reason' => $threedsPayment->getErrorMessage() ?? null
                ]);
            }
        } else {
            Log::info('ThreedCallback - Failed Payment', ['request' => $request->all()]);
            $payment->update([
                'status_id' => Payment::STATUS_3D_FAILED,
                'failure_reason' => $mdStatus //mdstatus sadece code geliyor ise codea göre responselere bakıp güncelleyelim.
            ]);
        }
    }
}
