<?php

namespace App\Models\Relations;

use App\Models\Gift;
use App\Models\User;
use App\Helpers\Variable;
use App\Models\GiftBasket;
use App\Models\Coin\CoinPackage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Casts\DatetimeTz;

/**
 * @mixin IdeHelperUserCoinTransaction
 */
class UserCoinTransaction extends Model
{
    public const
        TRANSACTION_TYPE_DEPOSIT = 1,
        TRANSACTION_TYPE_WITHDRAWAL = 2,
        TRANSACTION_TYPE_PURCHASE_GIFT = 3,
        TRANSACTION_TYPE_RECEIVE_GIFT = 4;

    public static array $transactionTypes = [
        self::TRANSACTION_TYPE_DEPOSIT => 'Coin Yükleme',
        self::TRANSACTION_TYPE_WITHDRAWAL => 'Coin Çekme',
        self::TRANSACTION_TYPE_PURCHASE_GIFT => 'Hediye Satın Alma',
        self::TRANSACTION_TYPE_RECEIVE_GIFT => 'Hediye Teslim Alma',
    ];

    protected $fillable = [
        'user_id',
        'amount',
        'wallet_type',
        'transaction_type',
        'coin_package_id',
        'gift_id',
        'related_user_id',
        'gift_basket_id',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => DatetimeTz::class,
            'updated_at' => DatetimeTz::class,
        ];
    }

    protected $appends = [
        'get_wallet_type',
        'get_transaction_type',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function coinPackage()
    {
        return $this->belongsTo(CoinPackage::class);
    }

    public function gift()
    {
        return $this->belongsTo(Gift::class);
    }

    public function relatedUser()
    {
        return $this->belongsTo(User::class, 'related_user_id');
    }

    public function giftBasket()
    {
        return $this->belongsTo(GiftBasket::class);
    }

    public function getWalletType(): Attribute
    {
        return Attribute::get(fn() => Variable::$walletTypes[$this->wallet_type]);
    }

    public function getTransactionType(): Attribute
    {
        return Attribute::get(fn() => self::$transactionTypes[$this->transaction_type]);
    }
}