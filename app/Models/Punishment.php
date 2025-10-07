<?php

namespace App\Models;

use App\Models\PunishmentCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Casts\DatetimeTz;

/**
 * @mixin IdeHelperPunishment
 */
class Punishment extends Model
{
    public const
        YELLOW_CARD = 1,
        RED_CARD = 2;

    public static array $cardTypes = [
        self::YELLOW_CARD => 'Sarı Kart',
        self::RED_CARD => 'Kırmızı Kart',
    ];

    protected $fillable = [
        'description',
        'card_type_id',
        'is_direct_punishment',
        'punishment_category_id',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => DatetimeTz::class,
            'updated_at' => DatetimeTz::class,
            'is_direct_punishment' => 'boolean'
        ];
    }

    protected $appends = [
        'get_card_type'
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(PunishmentCategory::class , 'punishment_category_id')->whereNotNull('parent_id');
    }

    public function getCardType(): Attribute
    {
        return Attribute::get(
            fn() => self::$cardTypes[$this->card_type_id] ?? null
        );
    }
}
