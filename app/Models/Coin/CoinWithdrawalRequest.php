<?php

namespace App\Models\Coin;

use App\Models\User;
use App\Helpers\Variable;
use App\Helpers\CommonHelper;
use App\Models\Common\Currency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Casts\DatetimeTz;

/**
 * @mixin IdeHelperCoinWithdrawalRequest
 */
class CoinWithdrawalRequest extends Model
{
    public const
        STATUS_PENDING = 1,
        STATUS_APPROVED = 2,
        STATUS_REJECTED = 3;

    public static array $statuses = [
        self::STATUS_PENDING => 'Bekliyor',
        self::STATUS_APPROVED => 'Onaylandı',
        self::STATUS_REJECTED => 'Reddedildi'
    ];

    public static array $statusColors = [
        self::STATUS_PENDING => 'warning',
        self::STATUS_APPROVED => 'success',
        self::STATUS_REJECTED => 'danger',
    ];


    protected $fillable = [
        'user_id',
        'coin_amount',
        'coin_unit_price',
        'coin_total_price',
        'currency_id',
        'wallet_type_id', // ileride diğer cüzdandan da çekim vs olursa diye eklendi
        'status_id',
        'reject_reason',
        'approved_at',
        'rejected_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => DatetimeTz::class,
            'updated_at' => DatetimeTz::class,
            'approved_at' => DatetimeTz::class,
            'rejected_at' => DatetimeTz::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    //start::Attributes
    public function getCoinUnitPrice(): Attribute
    {
        return Attribute::get(fn() => number_format($this->coin_unit_price, 2, ',', '.'));
    }
    public function getCoinTotalPrice(): Attribute
    {
        return Attribute::get(fn() => number_format($this->coin_total_price, 2, ',', '.'));
    }
    public function drawCoinUnitPrice(): Attribute
    {
        return Attribute::get(fn() => $this->currency->symbol . number_format($this->coin_unit_price, 2, ',', '.'));
    }
    public function drawCoinTotalPrice(): Attribute
    {
        return Attribute::get(fn() => $this->currency->symbol . number_format($this->coin_total_price, 2, ',', '.'));
    }
    public function getWalletType(): Attribute
    {
        return Attribute::get(fn() => Variable::$walletTypes[$this->wallet_type_id] ?? 'Bilinmiyor');
    }
    public function getStatus(): Attribute
    {
        return Attribute::get(fn() => self::$statuses[$this->status_id] ?? 'Bilinmiyor');
    }
    public function getStatusColor(): Attribute
    {
        return Attribute::get(fn() => self::$statusColors[$this->status_id] ?? 'secondary');
    }
    public function getCreatedAt(): Attribute
    {
        return Attribute::get(
            fn() => $this->created_at?->translatedFormat((new CommonHelper)->defaultDateTimeFormat(true))
        );
    }
    public function getApprovedAt(): Attribute
    {
        return Attribute::get(
            fn() => $this->approved_at?->translatedFormat((new CommonHelper)->defaultDateTimeFormat(true))
        );
    }
    public function getRejectedAt(): Attribute
    {
        return Attribute::get(
            fn() => $this->rejected_at?->translatedFormat((new CommonHelper)->defaultDateTimeFormat(true))
        );
    }
    //end::Attributes
}
