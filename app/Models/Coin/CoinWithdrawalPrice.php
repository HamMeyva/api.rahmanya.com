<?php

namespace App\Models\Coin;

use App\Models\Common\Currency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Casts\DatetimeTz;

/**
 * @mixin IdeHelperCoinWithdrawalPrice
 */
class CoinWithdrawalPrice extends Model
{
    protected $fillable = [
        'currency_id',
        'coin_unit_price',
    ];

    protected $appends = ['get_coin_unit_price'];

    protected function casts(): array
    {
        return [
            'created_at' => DatetimeTz::class,
            'updated_at' => DatetimeTz::class,
        ];
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function getCoinUnitPrice(): Attribute
    {
        return Attribute::get(fn() => number_format($this->coin_unit_price, 2, ',', '.'));
    }
}
