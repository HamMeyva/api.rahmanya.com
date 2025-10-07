<?php

namespace App\Models\Relations;

use App\Models\User;
use App\Casts\DatetimeTz;
use App\Helpers\CommonHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperUserDevice
 */
class UserDevice extends Model
{
    protected $table = 'user_devices';

    protected $fillable = [
        'device_type',
        'device_unique_id',
        'device_os',
        'device_os_version',
        'device_model',
        'device_brand',
        'device_ip',
        'user_id',
        'token',
        'is_banned',
        'banned_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => DatetimeTz::class,
            'updated_at' => DatetimeTz::class,
            'banned_at' => DatetimeTz::class,
            'is_banned' => 'boolean',
        ];
    }

    protected $appends = ['get_banned_at'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getBannedAt(): Attribute
    {
        return Attribute::get(
            fn() => $this->banned_at->translatedFormat((new CommonHelper)->defaultDateTimeFormat())
        );
    }
}
