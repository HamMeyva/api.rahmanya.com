<?php

namespace App\Models\Agora;

use Mongodb\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use App\Observers\Agora\AgoraChannelInviteObserver;
use App\Casts\DatetimeTz;

#[ObservedBy(AgoraChannelInviteObserver::class)]
/**
 * @mixin IdeHelperAgoraChannelInvite
 */
class AgoraChannelInvite extends Model
{
    public const STATUS_PENDING = 1,
        STATUS_ACCEPTED = 2,
        STATUS_REJECTED = 3;

    public static array $statuses = [
        self::STATUS_PENDING => 'Beklemede',
        self::STATUS_ACCEPTED => 'Onaylandı',
        self::STATUS_REJECTED => 'Reddedildi',
    ];

    protected $connection = 'mongodb';
    protected $collection = 'agora_channel_invites';
    protected $fillable = [
        'agora_channel_id',
        'agora_channel_data',
        'user_id',
        'user_data',
        'invited_user_id',
        'invited_user_data',
        'status_id',
        'invited_at',
        'responded_at',
    ];
    protected function casts(): array
    {
        return [
            'created_at' => DatetimeTz::class,
            'updated_at' => DatetimeTz::class,
            'invited_at' => DatetimeTz::class,
            'responded_at' => DatetimeTz::class,
        ];
    }

    protected $appends = [
        'get_status',
    ];

    /* start::Relations */
    public function user()
    {
        return User::find($this->user_id);
    }
    public function invitedUser()
    {
        return User::find($this->invited_user_id);
    }
    public function agoraChannel()
    {
        return AgoraChannel::find($this->agora_channel_id);
    }
    /* end::Relations */



    /* start::Attributes */
    public function getStatus(): Attribute
    {
        return Attribute::get(
            fn() => self::$statuses[$this->status_id] ?? null
        );
    }
    /* end::Attributes */
}
