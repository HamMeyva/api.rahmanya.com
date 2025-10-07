<?php

namespace App\Models\Morph;

use App\Models\User;
use App\Casts\DatetimeTz;
use App\Helpers\CommonHelper;
use App\Models\Ad\Advertiser;
use App\Models\Common\Currency;
use App\Models\PaymentDiscount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperPayment
 */
class Payment extends Model
{
    public static array $payableTypes = [
        'CoinPackage' => 'Shoot Coin',
        'Ad' => 'Reklam',
    ];

    public const
        STATUS_NEW = 1,
        STATUS_3D_FAILED = 2,
        STATUS_PENDING = 3,
        STATUS_WAITING_FOR_APPROVAL = 4,
        STATUS_COMPLETED = 5,
        STATUS_FAILED = 6,
        STATUS_CANCELLED = 7;

    public static array $statuses = [
        self::STATUS_NEW => 'Ödeme Başlatıldı',
        self::STATUS_3D_FAILED => '3D Doğrulanamadı',
        self::STATUS_PENDING => 'Beklemede',
        self::STATUS_WAITING_FOR_APPROVAL => 'Onay Bekliyor',
        self::STATUS_COMPLETED => 'Tamamlandı',
        self::STATUS_FAILED => 'Başarısız',
        self::STATUS_CANCELLED => 'İptal Edildi',
    ];

    public const
        CHANNEL_IYZICO = 1,
        CHANNEL_EFT = 2;

    public static array $channels = [
        self::CHANNEL_IYZICO => 'İyzico',
        self::CHANNEL_EFT => 'EFT',
    ];

    protected $fillable = [
        'payable_type',
        'payable_id',
        'sub_total',
        'discount_amount',
        'total_amount', // Ödenen net tutar
        'paid_at',
        'status_id',
        'transaction_id',
        'refund_id',
        'channel_id',
        'failure_reason',
        'user_id',
        'advertiser_id',
        'currency_id',
        'payable_data'
    ];

    protected $appends = ['get_channel', 'get_status', 'get_created_at', 'draw_total_amount'];

    protected function casts(): array
    {
        return [
            'created_at' => DatetimeTz::class,
            'updated_at' => DatetimeTz::class,
            'paid_at' => DatetimeTz::class,
            'payable_data' => 'json',
        ];
    }
    /* start::Relations */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function advertiser(): BelongsTo
    {
        return $this->belongsTo(Advertiser::class);
    }
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
    public function discounts(): HasMany
    {
        return $this->hasMany(PaymentDiscount::class);
    }
    /* end::Relations */


    /* start::Attributes */
    public function getPayableType(): Attribute
    {
        return Attribute::get(fn() => self::$payableTypes[$this->payable_type] ?? null);
    }

    public function getStatus(): Attribute
    {
        return Attribute::get(
            fn() => $this->status_id ? self::$statuses[$this->status_id] : null
        );
    }
    public function getChannel(): Attribute
    {
        return Attribute::get(
            fn() => $this->channel_id ? self::$channels[$this->channel_id] : null
        );
    }
    public function drawTotalAmount(): Attribute
    {
        return Attribute::get(function () {
            $priceText = number_format($this->total_amount, 2, ',', '.');
            $currencySymbol = $this->currency->symbol ?? '';
            return $currencySymbol . $priceText;
        });
    }
    public function getCreatedAt(): Attribute
    {
        return Attribute::get(
            fn() => $this->created_at->translatedFormat((new CommonHelper)->defaultDateTimeFormat())
        );
    }
    /* end::Attributes */
}
