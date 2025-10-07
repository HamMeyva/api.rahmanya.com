<?php

namespace App\Models;

use App\Helpers\CommonHelper;
use App\Models\Common\Country;
use App\Models\Common\Currency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Casts\DatetimeTz;
/**
 * @mixin IdeHelperCoupon
 */
class Coupon extends Model
{
    public const DISCOUNT_TYPE_FIXED = "fixed",
        DISCOUNT_TYPE_PERCENTAGE = "percentage";

    public static array $discountTypes = [
        self::DISCOUNT_TYPE_FIXED => 'Sabit',
        self::DISCOUNT_TYPE_PERCENTAGE => 'Yüzde',
    ];
    protected $fillable = [
        'code',
        'discount_type',
        'discount_amount',
        'currency_id',
        'start_date',
        'end_date',
        'is_active',
        'max_usage',
        'usage_count',
        'country_id',
    ];

    protected $appends = ['get_discount_type', 'draw_discount_amount'];

    protected function casts(): array
    {
        return [
            'created_at' => DatetimeTz::class,
            'updated_at' => DatetimeTz::class,
            'discount_amount' => 'integer',
            'start_date' => DatetimeTz::class,
            'end_date' => DatetimeTz::class,
            'is_active' => 'boolean',
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }


    public function getDiscountType(): Attribute
    {
        return Attribute::get(
            fn() => self::$discountTypes[$this->discount_type] ?? null
        );
    }

    
    public function getStartDate(): Attribute
    {
        return Attribute::get(
            fn() => $this->start_date->translatedFormat((new CommonHelper)->defaultDateTimeFormat())
        );
    }

    public function getEndDate(): Attribute
    {
        return Attribute::get(
            fn() => $this->end_date->translatedFormat((new CommonHelper)->defaultDateTimeFormat())
        );
    }

    public function drawDiscountAmount(): Attribute
    {
        return Attribute::get(function () {
            $discountAmount = $this->discount_amount;
            if($this->discount_type == self::DISCOUNT_TYPE_PERCENTAGE) {
                return "%{$discountAmount}";
            }

            return $this->currency->symbol . $discountAmount;
        });
    }

}
