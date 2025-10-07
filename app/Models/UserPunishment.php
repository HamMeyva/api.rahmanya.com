<?php

namespace App\Models;

use App\Models\User;
use App\Models\Punishment;
use App\Helpers\CommonHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Casts\DatetimeTz;

/**
 * @mixin IdeHelperUserPunishment
 */
class UserPunishment extends Model
{
    protected $connection = 'pgsql';

    protected $table = "user_punishments";

    protected $fillable = [
        'user_id',
        'punishment_id',
        'applied_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => DatetimeTz::class,
            'updated_at' => DatetimeTz::class,
            'applied_at' => DatetimeTz::class,
            'expires_at' => DatetimeTz::class,
        ];
    }

    protected $appends = ['get_applied_at', 'get_expires_at'];


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function punishment(): BelongsTo
    {
        return $this->belongsTo(Punishment::class);
    }

    /* start::Attributes */
    public function getAppliedAt(): Attribute
    {
        return Attribute::get(
            fn() => $this->applied_at?->translatedFormat((new CommonHelper)->defaultDateTimeFormat(true))
        );
    }

    public function getExpiresAt(): Attribute
    {
        return Attribute::get(
            fn() => $this->expires_at?->translatedFormat((new CommonHelper)->defaultDateTimeFormat(true))
        );
    }
    /* end::Attributes */
}
