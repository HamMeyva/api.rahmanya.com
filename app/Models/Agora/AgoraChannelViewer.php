<?php

namespace App\Models\Agora;

use Mongodb\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use App\Observers\Agora\AgoraChannelViewerObserver;
use App\Casts\DatetimeTz;
use App\Models\Traits\MongoTimestamps;

#[ObservedBy(AgoraChannelViewerObserver::class)]
/**
 * @mixin IdeHelperAgoraChannelViewer
 */
class AgoraChannelViewer extends Model
{
    use MongoTimestamps;
    
    public const STATUS_ACTIVE = 1,
        STATUS_LEFT = 2,
        STATUS_BANNED = 3;

    public static array $statuses = [
        self::STATUS_ACTIVE => 'Aktif',
        self::STATUS_LEFT => 'Ayrıldı',
        self::STATUS_BANNED => 'Yasaklandı',
    ];

    public const ROLE_HOST = 1,
        ROLE_GUEST = 2,
        ROLE_VIEWER = 3;

    public static array $roles = [
        self::ROLE_HOST => 'Yayınıcı',
        self::ROLE_GUEST => 'Konuk',
        self::ROLE_VIEWER => 'İzleyici',
    ];

    protected $connection = 'mongodb';

    protected $collection = 'agora_channel_viewers';

    protected $appends = ['get_status', 'get_role', 'get_watch_duration'];

    protected $fillable = [
        'agora_channel_id',
        'agora_channel_data',

        'role_id',
        'status_id',
        'user_id',
        'user_data',

        'token',
        
        'joined_at',
        'left_at',
        'watch_duration',

        'is_following_streamer',

        'total_sent_gift_count',
        'total_sent_coin_value',
        'total_received_gift_count',
        'total_received_coin_value'
    ];

    protected function casts(): array{
        return [
            'joined_at' => DatetimeTz::class,
            'left_at' => DatetimeTz::class,
            'is_following_streamer' => 'boolean',
        ];
    }

    public function getStatus(): Attribute
    {
        return Attribute::get(
            fn() => self::$statuses[$this->status_id] ?? null
        );
    }

    public function getRole(): Attribute
    {
        return Attribute::get(
            fn() => self::$roles[$this->role_id] ?? null
        );
    }



    /*const STATUS_ACTIVE = 'active';   // Aktif izliyor
    const STATUS_LEFT = 'left';       // Ayrıldı
    const STATUS_BANNED = 'banned';   // Yasaklandı

    protected $connection = 'mongodb';

    protected $collection = 'agora_channel_viewers';

    protected $fillable = [
        'agora_channel_id',
        'agora_channel_data',
        'user_id',
        'user_data',
        'joined_at',
        'left_at',
        'watch_duration',
        'device_info',
        'coins_spent',
        'gifts_sent',
        'messages_count',
        'is_following_streamer',
        'roles',
        'status',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'watch_duration' => 'integer',
        'coins_spent' => 'integer',
        'gifts_sent' => 'integer',
        'messages_count' => 'integer',
        'is_following_streamer' => 'boolean',
    ];

 
    public function getTable(): string
    {
        return 'agora_channel_viewers';
    }

    public function agoraChannel()
    {
        return AgoraChannel::find($this->agora_channel_id);
    }


    public function user()
    {
        return User::find($this->user_id);
    }


    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }


    public function scopeForChannel($query, string $channelId)
    {
        return $query->where('agora_channel_id', $channelId);
    }

    public function scopeWithRole($query, string $role)
    {
        return $query->where('roles', 'all', [$role]);
    }

    public function getFormattedWatchDurationAttribute(): string
    {
        return (new CommonHelper())->formatDuration($this->watch_duration ?? 0);
    }*/
}
