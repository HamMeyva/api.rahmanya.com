<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\User;
use App\Helpers\CommonHelper;
use Mongodb\Laravel\Eloquent\Model;
use App\Models\Traits\ReportProblemTrait;
use App\Observers\StoryObserver;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use App\Casts\DatetimeTz;
use App\Models\Traits\MongoTimestamps;

/**
 * Story model for user stories
 *
 * @mixin IdeHelperStory
 */

/**
 * @mixin IdeHelperStory
 */
#[ObservedBy(StoryObserver::class)]
class Story extends Model
{
    use ReportProblemTrait, SoftDeletes, MongoTimestamps;

    public const
        STATUS_PROCESSING = 1,
        STATUS_SUCCESS = 2,
        STATUS_FAILED = 3;

    public static array $statuses = [
        self::STATUS_PROCESSING => 'İşleniyor',
        self::STATUS_SUCCESS => 'Başarılı',
        self::STATUS_FAILED => 'Başarısız'
    ];

    public static array $statusColors = [
        self::STATUS_PROCESSING => 'warning',
        self::STATUS_SUCCESS => 'success',
        self::STATUS_FAILED => 'danger',
    ];


    protected $connection = 'mongodb';
    protected $collection = 'stories';

    protected $fillable = [
        'user_id',
        'collection_uuid',
        'media_guid',
        'media_type',
        'media_url',
        'thumbnail_url',
        'caption',
        'location',
        'is_private',
        'expires_at',
        'is_expired',
        'views_count',
        'likes_count',
        'status_id',
        'metadata',
        'user_data',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => DatetimeTz::class,
            'is_private' => 'boolean',
        ];
    }


    public function user()
    {
        $userId = $this->user_id;
        if (!$userId) {
            return null;
        }

        if (isset($this->user_data) && !empty($this->user_data)) {
            return $this->getEmbeddedUser();
        }

        return User::find($userId);
    }

    public function getEmbeddedUser()
    {
        // Eğer user_data yoksa null döndür
        if (!isset($this->user_data) || empty($this->user_data)) {
            return null;
        }

        // Eğer user_data bir nesne değilse, nesneye dönüştür
        $userData = is_object($this->user_data) ? $this->user_data : (object)$this->user_data;

        // User nesnesi oluştur ve özellikleri ata
        $user = new User();

        // user_data'daki tüm özellikleri User nesnesine aktar
        foreach ($userData as $key => $value) {
            $user->$key = $value;
        }

        return $user;
    }


    public function story_likes()
    {
        return $this->hasMany(StoryLike::class);
    }

    /**
     * Get the story views
     */
    public function story_views()
    {
        return $this->hasMany(StoryView::class);
    }

    /**
     * Get the media URL attribute
     */
    public function getMediaUrlAttribute($value)
    {
        if (isset($this->attributes['media_url']) && !empty($this->attributes['media_url'])) {
            return $this->attributes['media_url'];
        }

        $hostName = config('bunnycdn-library.host_name', 'https://vz-5d4dcc09-d8f.b-cdn.net/');
        $mediaGuid = $this->media_guid ?? null;

        if ($mediaGuid) {
            return "{$hostName}{$mediaGuid}/story.jpg";
        }

        return null;
    }

    /**
     * Get the thumbnail URL attribute
     */
    public function getThumbnailUrlAttribute($value)
    {
        if (isset($this->attributes['thumbnail_url']) && !empty($this->attributes['thumbnail_url'])) {
            return $this->attributes['thumbnail_url'];
        }

        $hostName = config('bunnycdn-library.host_name', 'https://vz-5d4dcc09-d8f.b-cdn.net/');
        $mediaGuid = $this->media_guid ?? null;

        if ($mediaGuid) {
            return "{$hostName}{$mediaGuid}/thumbnail.jpg";
        }

        return null;
    }

    /**
     * Check if the story is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Get remaining time until expiration in seconds
     */
    public function getRemainingTimeAttribute(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }

        $now = Carbon::now();
        if ($this->expires_at->isPast()) {
            return 0;
        }

        return $now->diffInSeconds($this->expires_at);
    }

    /**
     * Scope a query to only include active stories
     */
    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', Carbon::now())
            ->where('status', 'active');
    }

    /**
     * Scope a query to only include public stories
     */
    public function scopePublic($query)
    {
        return $query->where('is_private', false);
    }

    /**
     * Scope a query to only include stories from users that the given user follows
     */
    public function scopeFromFollowing($query, $userId)
    {
        // Get users that the current user follows
        $followingIds = \App\Models\Follow::where('follower_id', $userId)
            ->where('status', 'accepted')
            ->whereNull('deleted_at')
            ->pluck('followed_id')
            ->toArray();

        return $query->whereIn('user_id', $followingIds);
    }

    /**
     * Scope a query to only include stories from the given user
     */
    public function scopeFromUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * MongoDB için gerekli indeksleri oluştur
     */
    public static function createIndexes()
    {
        self::raw(function ($collection) {
            $collection->createIndex(['user_id' => 1]);
            $collection->createIndex(['created_at' => -1]);
            $collection->createIndex(['expires_at' => 1]);
            $collection->createIndex(['status' => 1]);
            $collection->createIndex(['is_private' => 1]);
        });
    }

    public function getCreatedAt(): Attribute
    {
        return Attribute::get(
            fn() => $this->created_at?->translatedFormat((new CommonHelper)->defaultDateTimeFormat(true))
        );
    }

    public function getUpdatedAt(): Attribute
    {
        return Attribute::get(
            fn() => $this->updated_at?->translatedFormat((new CommonHelper)->defaultDateTimeFormat(true))
        );
    }

    public function getDeletedAt(): Attribute
    {
        return Attribute::get(
            fn() => $this->deleted_at?->translatedFormat((new CommonHelper)->defaultDateTimeFormat(true))
        );
    }
}
