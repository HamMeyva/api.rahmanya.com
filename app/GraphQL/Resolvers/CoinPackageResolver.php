<?php

namespace App\GraphQL\Resolvers;

use App\Models\Admin;
use App\Helpers\CommonHelper;
use App\Http\Resources\CoinPackageDataResource;
use App\Models\Morph\Payment;
use App\Services\CouponService;
use App\Models\Coin\CoinPackage;
use App\Models\PaymentDiscount;
use Illuminate\Support\Facades\Auth;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Notification;
use App\Notifications\Admin\EftPaymentCreated;
use Illuminate\Validation\ValidationException;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class CoinPackageResolver
{
    public function index($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $countryId = $args["country_id"] ?? null;

        return CoinPackage::query()
            ->with('country')
            ->when($countryId, function ($query) use ($countryId) {
                return $query->where("country_id", $countryId);
            })->get();
    }

    /**
     * Coin Package satın alma
     */
    public function purchase($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        $authUser = Auth::user();
        $input = $args['input'];

        $validator = Validator::make($input, [
            'coin_package_id' => 'required'
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $coinPackage = CoinPackage::find($input['coin_package_id']);

        if (!$coinPackage) {
            return [
                'success' => false,
                'message' => 'Paket bulunamadı bulunamadı, tekrar deneyiniz'
            ];
        }


        $couponDiscountAmount = 0;
        if ($input['coupon_code']) {
            $couponService = app(CouponService::class);
            $coupon = $couponService->findValidCoupon($input['coupon_code'] ?? '');
            if (!$coupon) {
                return [
                    'success' => false,
                    'message' => 'Kupon geçersiz veya süresi dolmuş'
                ];
            }

            $couponDiscountAmount = $couponService->calculateDiscount($coupon, $coinPackage->get_final_price);
        }

        $totalDiscountAmount = $coinPackage->get_discount_amount + $couponDiscountAmount;
        $transactionId = (new CommonHelper)->generateTransactionId();

        $payment = $coinPackage->payment()->create([
            'status_id' => Payment::STATUS_NEW,
            'sub_total' => $coinPackage->price,
            'discount_amount' => $totalDiscountAmount,
            'total_amount' => $coinPackage->get_final_price,
            'transaction_id' => $transactionId,
            'channel_id' => Payment::CHANNEL_IYZICO,
            'user_id' => $authUser?->id,
            'currency_id' => $coinPackage->currency_id,
            'payable_data' => CoinPackageDataResource::make($coinPackage)
        ]);
        $payment->load('user', 'currency');

        if ($coinPackage->is_discount) {
            $payment->discounts()->create([
                'source_id' => PaymentDiscount::SOURCE_PACKAGE,
                'description' => PaymentDiscount::$sources[PaymentDiscount::SOURCE_PACKAGE] . " indirimi.",
                'amount' => $coinPackage->get_discount_amount,
            ]);
        }

        if ($input['coupon_code']) {
            $payment->discounts()->create([
                'source_id' => PaymentDiscount::SOURCE_COUPON,
                'description' => PaymentDiscount::$sources[PaymentDiscount::SOURCE_COUPON] . " indirimi.",
                'amount' => $couponDiscountAmount,
                'coupon_code' => $input['coupon_code']
            ]);

            $couponService->incrementUsageCount($coupon);
        }

        return [
            'success' => true,
            'message' => 'Ödeme adımına yönlendiriliyorsunuz...',
            'callback_url' => (new CommonHelper)->getIyzicoCallbackUrl(),
            'payment' => $payment,
        ];
    }

    public function createEftPayment($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        $authUser = Auth::user();
        $input = $args['input'];

        $validator = Validator::make($input, [
            'coin_package_id' => 'required'
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $coinPackage = CoinPackage::find($input['coin_package_id']);

        if (!$coinPackage) {
            return [
                'success' => false,
                'message' => 'Paket bulunamadı bulunamadı, tekrar deneyiniz'
            ];
        }

        $couponDiscountAmount = 0;
        if ($input['coupon_code']) {
            $couponService = app(CouponService::class);
            $coupon = $couponService->findValidCoupon($input['coupon_code'] ?? '');
            if (!$coupon) {
                return [
                    'success' => false,
                    'message' => 'Kupon geçersiz veya süresi dolmuş'
                ];
            }

            $couponDiscountAmount = $couponService->calculateDiscount($coupon, $coinPackage->get_final_price);
        }


        $transactionId = (new CommonHelper)->generateTransactionId();

        $totalDiscountAmount = $coinPackage->get_discount_amount + $couponDiscountAmount;
        $payment = $coinPackage->payment()->create(attributes: [
            'status_id' => Payment::STATUS_WAITING_FOR_APPROVAL,
            'sub_total' => $coinPackage->price,
            'discount_amount' => $totalDiscountAmount,
            'total_amount' => $coinPackage->get_final_price,
            'transaction_id' => $transactionId,
            'channel_id' => Payment::CHANNEL_EFT,
            'user_id' => $authUser?->id,
            'currency_id' => $coinPackage->currency_id,
            'payable_data' => CoinPackageDataResource::make($coinPackage)
        ]);

        if ($coinPackage->is_discount) {
            $payment->discounts()->create([
                'source_id' => PaymentDiscount::SOURCE_PACKAGE,
                'description' => PaymentDiscount::$sources[PaymentDiscount::SOURCE_PACKAGE] . " indirimi.",
                'amount' => $coinPackage->get_discount_amount,
            ]);
        }

        if ($input['coupon_code']) {
            $payment->discounts()->create([
                'source_id' => PaymentDiscount::SOURCE_COUPON,
                'description' => PaymentDiscount::$sources[PaymentDiscount::SOURCE_COUPON] . " indirimi.",
                'amount' => $couponDiscountAmount,
                'coupon_code' => $input['coupon_code']
            ]);

            $couponService->incrementUsageCount($coupon);
        }

        /* Start::Notification */
        $admins = Admin::all();
        Notification::send($admins, new EftPaymentCreated($payment));
        /* End::Notification */

        return [
            'success' => true,
            'message' => 'Ödeme bildiriminiz gönderildi, en kısa sürede kontrol edilerek onaylanacaktır.'
        ];
    }
}
