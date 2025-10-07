<?php

namespace App\Models\Coin;

use App\Helpers\Variable;
use App\Models\Common\Country;
use App\Models\Common\Currency;
use App\Models\Traits\PaymentTrait;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use App\Models\Relations\UserCoinTransaction;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Notifications\CoinDepositNotification;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Casts\DatetimeTz;

/**
 * @mixin IdeHelperCoinPackage
 */
class CoinPackage extends Model
{
    use SoftDeletes, PaymentTrait;

    protected $connection = "pgsql";

    protected $table = "coin_packages";

    protected $fillable = [
        'coin_amount',
        'price',
        'is_discount',
        'discounted_price',
        'currency_id',
        'is_active',
        'country_id',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => DatetimeTz::class,
            'updated_at' => DatetimeTz::class,
            'deleted_at' => DatetimeTz::class,
            'is_active' => 'boolean',
            'is_discount' => 'boolean',
        ];
    }

    protected $appends = ['get_final_price', 'draw_final_price', 'get_price', 'draw_price', 'draw_discounted_price', 'get_discount_amount', 'draw_discount_amount'];


    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function getPrice(): Attribute
    {
        return Attribute::get(
            fn() => number_format($this->price, 2, ',', '.')
        );
    }

    public function drawDiscountedPrice(): Attribute
    {
        return Attribute::get(
            fn() => number_format($this->discounted_price, 2, ',', '.')
        );
    }

    public function getFinalPrice(): Attribute
    {
        // Eğer indirim var ise indirim dahil son fiyat
        return Attribute::get(fn() => $this->is_discount ? $this->discounted_price : $this->price);
    }

    public function getDiscountAmount(): Attribute
    {
        return Attribute::get(fn() => $this->is_discount ? $this->price - $this->discounted_price : 0);
    }

    public function drawDiscountAmount(): Attribute
    {
        return Attribute::get(fn() => number_format($this->get_discount_amount, 2, ',', '.'));
    }


    public function drawPrice(): Attribute
    {
        return Attribute::get(function () {
            $priceText = number_format($this->price, 2, ',', '.');
            $currencySymbol = $this->currency->symbol ?? '';
            return $currencySymbol . $priceText;
        });
    }

    public function drawFinalPrice(): Attribute
    {
        return Attribute::get(function () {
            $priceText = number_format($this->get_final_price, 2, ',', '.');
            $currencySymbol = $this->currency->symbol ?? '';
            return $currencySymbol . $priceText;
        });
    }

    public function paymentCallbackTransactions($payment): void
    {
        $user = $payment->user;
        if (!$user) {
            Log::info('Models\Coin\CoinPackage::PaymentCallbackTransactions - User not found', ['payment' => $payment]);
            return;
        }

        $transaction = $user->coin_transactions()->create([
            'user_id' => $user->id,
            'amount' => $this->coin_amount,
            'wallet_type' => Variable::WALLET_TYPE_DEFAULT,
            'transaction_type' => UserCoinTransaction::TRANSACTION_TYPE_DEPOSIT,
            'coin_package_id' => $this->id,
        ]);

        $user->increment('coin_balance', $this->coin_amount);

        // ** START::Notification
        $user->notify(new CoinDepositNotification($transaction));
        // ** END::Notification
    }
}
