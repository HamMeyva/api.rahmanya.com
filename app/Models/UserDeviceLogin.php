<?php

namespace App\Models;

use App\Casts\DatetimeTz;
use App\Helpers\CommonHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * @mixin IdeHelperUserDeviceLogin
 */
class UserDeviceLogin extends Model
{
    protected $fillable = [
        'user_id',
        'device_type',
        'device_unique_id',
        'device_os',
        'device_os_version',
        'device_model',
        'device_brand',
        'device_ip',
        'access_token',
        'last_activity_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => DatetimeTz::class,
            'updated_at' => DatetimeTz::class,
            'last_activity_at' => DatetimeTz::class,
        ];
    }

    protected $append = [
        'get_last_activity_at'
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getLastActivityAt(): Attribute
    {
        return Attribute::get(
            fn() => $this->last_activity_at->translatedFormat((new CommonHelper)->defaultDateTimeFormat())
        );
    }
}
