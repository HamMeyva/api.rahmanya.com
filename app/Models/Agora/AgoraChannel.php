<?php

namespace App\Models\Agora;

use App\Models\User;
use App\Helpers\CommonHelper;
use App\Services\BunnyCdnService;
use App\Models\LiveStreamCategory;
use App\Models\Challenge\Challenge;
use Mongodb\Laravel\Eloquent\Model;
use MongoDB\Laravel\Relations\HasOne;
use App\Models\Traits\ReportProblemTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Casts\DatetimeTz;
use App\Models\Traits\MongoTimestamps;

/*
    Redis Keys

    Key Formats Used:
    -------------------------------------
    agora_channel:{channelId}:streamer:{recipientUserId}:gift_senders

    agora_channel:{channelId}:viewer_count                       // izleyici sayısı

    agora_channel:{channelId}:viewers                            // izleyici idleri tutulur

    //---

    challenge:{challengeId}:{roundNumber}:streamer_total_coins       // Pk roundu boyunca yayıncı bazlı toplam coin tutulur

    challenge:{challengeId}:{roundNumber}:team_total_coins           // Pk roundu boyunca takım bazlı toplam coin tutulur

    challenge:{challengeId}:{roundNumber}:team_wins                  // Pk roundu boyunca takım bazlı win sayısı tutulur
*/

/**
 * @mixin IdeHelperAgoraChannel
 */
class AgoraChannel extends Model
{
    use HasFactory, SoftDeletes, ReportProblemTrait, MongoTimestamps;

    public const STATUS_WAITING = 1,
        STATUS_LIVE = 2,
        STATUS_ENDED = 3,
        STATUS_BANNED = 4;

    public static array $statuses = [
        self::STATUS_WAITING => 'Hazırlık aşamasında',
        self::STATUS_LIVE => 'Yayında',
        self::STATUS_ENDED => 'Sona ermiş',
        self::STATUS_BANNED => 'Yasaklanmış'
    ];

    protected $connection = 'mongodb';
    protected $collection = 'agora_channels';
    protected $fillable = [
        'channel_name',
        'user_id',
        'is_online',
        'language_id',
        'title',
        'description',
        'thumbnail_path',
        'stream_key',
        'rtmp_url',
        'playback_url',
        'viewer_count',
        'max_viewer_count',
        'total_likes',
        'total_gifts',
        'total_coins_earned',
        'tags',
        'category_id',
        'is_featured',
        'settings',
        'status_id',
        'started_at',
        'ended_at',
        'is_challenge_active',
        'duration'
    ];

    public function casts(): array
    {
        return [
            'is_online' => 'boolean',
            'is_featured' => 'boolean',
            'started_at' => DatetimeTz::class,
            'ended_at' => DatetimeTz::class,
            'is_challenge_active' => 'boolean',
        ];
    }

    protected $appends = [
        'duration',
        'formatted_viewer_count',
        'status',
        'total_messages',
        'total_likes',
        'total_gifts',
        'total_coins_earned',
        'viewer_count',
        'max_viewer_count',
        'thumbnail_url',
    ];

    public function activeChallenge(): HasOne
    {
        return $this->hasOne(Challenge::class, 'agora_channel_id', '_id')->where('status_id', Challenge::STATUS_ACTIVE);
    }

    public function user()
    {
        return User::find($this->user_id);
    }

    public function gifts()
    {
        return AgoraChannelGift::where('agora_channel_id', $this->id)->get();
    }

    public function viewers()
    {
        return AgoraChannelViewer::where('agora_channel_id', $this->id)->get();
    }

    public function messages()
    {
        return AgoraChannelMessage::where('agora_channel_id', $this->id)->get();
    }

    public function statistics()
    {
        return AgoraStreamStatistic::where('agora_channel_id', $this->id)->first();
    }

    public function category()
    {
        return $this->belongsTo(LiveStreamCategory::class, 'category_id', 'id');
    }

    public function getDurationAttribute(): int
    {
        if (isset($this->attributes['duration']) && $this->attributes['duration']) {
            return (int) $this->attributes['duration'];
        }

        if (!$this->started_at) {
            return 0;
        }

        $endTime = $this->ended_at ?? now();
        return $this->started_at->diffInSeconds($endTime);
    }

    public function getFormattedViewerCountAttribute(): string
    {
        return (new CommonHelper())->formatNumber($this->viewer_count ?? 0, 'short');
    }

    /**
     * Get the status string for GraphQL
     * 
     * @return string
     */
    public function getStatusAttribute(): string
    {
        // Map numeric status_id to string status values required by GraphQL
        $statusMap = [
            self::STATUS_WAITING => 'waiting',
            self::STATUS_LIVE => 'online',
            self::STATUS_ENDED => 'ended',
            self::STATUS_BANNED => 'banned'
        ];

        return $statusMap[$this->status_id] ?? 'unknown';
    }

    /**
     * Get the total messages count for GraphQL
     * Ensures the field is never null as required by GraphQL schema
     * 
     * @return int
     */
    public function getTotalMessagesAttribute(): int
    {
        // If the attribute exists, use it, otherwise default to 0
        return $this->attributes['total_messages'] ?? 0;
    }

    /**
     * Get the total likes count for GraphQL
     * 
     * @return int
     */
    public function getTotalLikesAttribute(): int
    {
        return $this->attributes['total_likes'] ?? 0;
    }

    /**
     * Get the total gifts count for GraphQL
     * 
     * @return int
     */
    public function getTotalGiftsAttribute(): int
    {
        return $this->attributes['total_gifts'] ?? 0;
    }

    /**
     * Get the total coins earned for GraphQL
     * 
     * @return int
     */
    public function getTotalCoinsEarnedAttribute(): int
    {
        return $this->attributes['total_coins_earned'] ?? 0;
    }

    /**
     * Get the viewer count for GraphQL
     * 
     * @return int
     */
    public function getViewerCountAttribute(): int
    {
        return $this->attributes['viewer_count'] ?? 0;
    }

    /**
     * Get the max viewer count for GraphQL
     * 
     * @return int
     */
    public function getMaxViewerCountAttribute(): int
    {
        return $this->attributes['max_viewer_count'] ?? 0;
    }

    /**
     * Get is_online field for GraphQL
     * 
     * @return bool
     */
    public function getIsOnlineAttribute(): bool
    {
        return (bool)($this->attributes['is_online'] ?? false);
    }

    /**
     * Get is_featured field for GraphQL
     * 
     * @return bool
     */
    public function getIsFeaturedAttribute(): bool
    {
        return (bool)($this->attributes['is_featured'] ?? false);
    }

    public function scopeActive($query)
    {
        return $query->where('status_id', self::STATUS_LIVE)
            ->where('is_online', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function getStartedAt(): Attribute
    {
        return Attribute::get(
            fn() => $this->started_at?->translatedFormat((new CommonHelper)->defaultDateTimeFormat(true))
        );
    }

    public function getEndedAt(): Attribute
    {
        return Attribute::get(
            fn() => $this->ended_at?->translatedFormat((new CommonHelper)->defaultDateTimeFormat(true))
        );
    }

    public function thumbnailUrl(): Attribute
    {
        return Attribute::get(function () {
            if (!$this->thumbnail_path)
                return null;

            $bunnyCdnService = app(BunnyCdnService::class);
            return $bunnyCdnService->getStorageUrl($this->thumbnail_path);
        });
    }
}
