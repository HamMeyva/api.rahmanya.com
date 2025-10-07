<?php

namespace App\Models\Challenge;

use Mongodb\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Observers\Challenge\ChallengeInviteObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use App\Casts\DatetimeTz;
use App\Models\Traits\MongoTimestamps;

#[ObservedBy(ChallengeInviteObserver::class)]
/**
 * @mixin IdeHelperChallengeInvite
 */
class ChallengeInvite extends Model
{
    use MongoTimestamps;
    
    public const STATUS_WAITING = 1,
        STATUS_ACCEPTED = 2,
        STATUS_REJECTED = 3,
        STATUS_CANCELLED = 4,
        STATUS_EXPIRED = 5;

    public static array $statuses = [
        self::STATUS_WAITING => 'Onay Bekliyor',
        self::STATUS_ACCEPTED => 'Onaylandı',
        self::STATUS_REJECTED => 'Reddedildi',
        self::STATUS_CANCELLED => 'İptal Edildi',
        self::STATUS_EXPIRED => 'Süresi Dolmuş',
    ];

    protected $connection = 'mongodb';
    protected $collection = 'challenge_invites';
    protected $fillable = [
        'agora_channel_id',
        'agora_channel_data',
        'sender_user_id',
        'sender_user_data',
        'status_id',
        'expires_at',
        'invited_users_data', //davet atıldıgı anda yayında olan ve davet gönderilen diğer userlar
        'teammate_user_id',
        'teammate_user_data',
        'round_duration',
        'coin_amount',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => DatetimeTz::class,
        ];
    }

    /* start::Attributes */
    public function getStatus(): Attribute
    {
        return Attribute::get(
            fn() => self::$statuses[$this->status_id] ?? null
        );
    }
    /* end::Attributes */
}
