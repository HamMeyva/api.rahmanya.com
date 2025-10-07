<?php

namespace App\Services;

use Exception;
use Carbon\Carbon;
use App\Models\Coupon;
use Illuminate\Support\Facades\Log;

class CouponService
{
    public function findValidCoupon(string $code): ?Coupon
    {
        $now = Carbon::now();

        $coupon = Coupon::with(['country', 'currency'])
            ->where('code', $code)
            ->where('is_active', true)
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->where(function ($query) {
                $query->whereNull('max_usage')->orWhereColumn('usage_count', '<', 'max_usage');
            })
            ->first();

        return $coupon;
    }

    public function calculateDiscount(Coupon $coupon, float $amount): float
    {
        if ($coupon->discount_type === Coupon::DISCOUNT_TYPE_FIXED) {
            return min($coupon->discount_amount, $amount);
        } elseif ($coupon->discount_type === Coupon::DISCOUNT_TYPE_PERCENTAGE) {
            return round($amount * ($coupon->discount_amount / 100), 2);
        }

        return 0.0;
    }

    public function incrementUsageCount(Coupon $coupon): void
    {
        try {
            $coupon->increment('usage_count');
        } catch (Exception $e) {
            Log::error('CouponService::incrementUsageCount() - Coupon usage count could not be incremented.', [
                'coupon_id' => $coupon->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function decrementUsageCount(Coupon $coupon): void
    {
        try {
            $coupon->decrement('usage_count');
        } catch (Exception $e) {
            Log::error('CouponService::decrementUsageCount() - Coupon usage count could not be decremented.', [
                'coupon_id' => $coupon->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
