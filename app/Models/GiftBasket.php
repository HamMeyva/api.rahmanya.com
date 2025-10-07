<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Casts\DatetimeTz;

/**
 * @mixin IdeHelperGiftBasket
 */
class GiftBasket extends Model
{
    use SoftDeletes;
    
    protected $connection = "pgsql";
    protected $table = "gift_baskets";

    protected $fillable = [
        'user_id',
        'gift_id',
        'custom_unit_price',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => DatetimeTz::class,
            'updated_at' => DatetimeTz::class,
            'deleted_at' => DatetimeTz::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gift(): BelongsTo
    {
        return $this->belongsTo(Gift::class);
    }
}
