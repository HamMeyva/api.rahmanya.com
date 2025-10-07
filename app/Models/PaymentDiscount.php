<?php

namespace App\Models;

use App\Models\Morph\Payment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperPaymentDiscount
 */
class PaymentDiscount extends Model
{
    public const SOURCE_COUPON = 1, SOURCE_PACKAGE = 2;
    public static array $sources = [
        self::SOURCE_COUPON => 'Kupon',
        self::SOURCE_PACKAGE => 'Ürün/Hizmet',
    ];

    protected $connection = 'pgsql';

    protected $table = 'payment_discounts';

    protected $fillable = [
        'payment_id',
        'source_id',
        'coupon_code',
        'description',
        'amount',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function getSource(): Attribute
    {
        return Attribute::get(
            fn() => self::$sources[$this->source_id] ?? null
        );
    }
}
