<?php

namespace App\Models;

use App\Models\VideoLike;
use App\Models\VideoComment;
use App\Helpers\CommonHelper;
use App\Observers\VideoObserver;
use App\Services\BunnyCdnService;
use Illuminate\Support\Facades\Log;
use Mongodb\Laravel\Eloquent\Model;
use App\Models\Traits\MongoTimestamps;
use App\Models\Traits\ReportProblemTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy(VideoObserver::class)]
/**
 * @mixin IdeHelperVideo
 */
class Video extends Model
{
    use ReportProblemTrait, SoftDeletes, MongoTimestamps;

    protected $connection = 'mongodb';
    protected $collection = 'videos';

    protected $primaryKey = '_id';
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'collection_uuid',
        'video_guid',
        'is_private',
        'is_commentable',
        'is_featured',
        'tags',
        'mentions',
        // New fields for TikTok-like functionality
        'duration',
        'width',
        'height',
        'framerate',
        'status',
        'metadata',
        'team_tags',
        'location',
        'language',
        'content_rating',
        'engagement_score',
        'trending_score',
        'visibility',
        'processing_status',
        'is_sport',
        // Embedded user data fields
        'user_data',
        // Engagement counters
        'views_count',
        'play_count',
        'likes_count',
        'report_count',
        'comments_count',
        'completed_count',
        'is_played',
        'thumbnail_filename',
        'temp_thumbnail_duration', // for video-edito
        'temp_thumbnail_image', // for video-edito
    ];

    protected function casts(): array
    {
        return [
            'is_banned' => 'boolean',
            'is_private' => 'boolean',
            'is_commentable' => 'boolean',
            'is_featured' => 'boolean',
            'views_count' => 'integer',
            'play_count' => 'integer',
            'likes_count' => 'integer',
            'report_count' => 'integer',
            'comments_count' => 'integer',
            'completed_count' => 'integer',
            'duration' => 'float',
            'width' => 'integer',
            'height' => 'integer',
            'framerate' => 'float',
            'engagement_score' => 'float',
            'trending_score' => 'float',
            'is_sport' => 'boolean',
            'is_played' => 'boolean',
        ];
    }

    protected $append = [
        'get_created_at',
        'get_updated_at',
    ];

    public function video_likes(): HasMany
    {
        return $this->hasMany(VideoLike::class);
    }

    public function video_comments(): HasMany
    {
        return $this->hasMany(VideoComment::class);
    }

    /**
     * Get the user that owns the video
     * Custom implementation for cross-database relationship
     *
     * First tries to use embedded user data for better performance
     * Only falls back to SQL database query if embedded data isn't available
     *
     * Enhanced to handle non-existent users for GraphQL non-nullable fields
     */
    public function user()
    {
        // MongoDB ile SQL arasında doğrudan ilişki kurulamaz
        // Bu nedenle manuel olarak User modelini getiriyoruz
        $userId = $this->user_id;
        if (!$userId) {
            Log::warning('Video has no user_id', ['video_id' => $this->id]);
            return null;
        }

        // First try to use embedded user data if available
        $embeddedUser = $this->getEmbeddedUser();
        if ($embeddedUser) {
            return $embeddedUser;
        }

        // Fall back to SQL database query if embedded data isn't available
        $user = User::find($userId);

        // If user not found, log warning
        if (!$user) {
            \Log::warning('User not found for video', [
                'video_id' => $this->id,
                'user_id' => $userId
            ]);
        }

        return $user;
    }

    /**
     * Get the video URL based on the stored video_guid
     */
    public function getVideoUrlAttribute()
    {
        if (!$this->video_guid) return null;

        return app(BunnyCdnService::class)->getStreamUrl($this->video_guid);

        //aaaaaa

        // Eğer video_url zaten varsa ve HLS formatında ise, düzgün format kontrolü yap
        if (isset($this->attributes['video_url']) && !empty($this->attributes['video_url']) && strpos($this->attributes['video_url'], 'playlist.m3u8') !== false) {
            $url = $this->attributes['video_url'];

            // URL'nin https:// ile başlayıp başlamadığını kontrol et
            if (strpos($url, 'https://') !== 0) {
                // Domain ve GUID'in düzgün ayrılıp ayrılmadığını kontrol et
                if (preg_match('/vz-[\w-]+\.b-cdn\.net([\w-]+)\/playlist\.m3u8/', $url, $matches)) {
                    // Domain ve GUID arasında / karakteri eksik
                    $domain = substr($url, 0, strpos($url, $matches[1]));
                    $guid = $matches[1];
                    return "https://{$domain}/{$guid}/playlist.m3u8";
                } else {
                    // Sadece https:// eksik
                    return "https://{$url}";
                }
            }

            return $url;
        }

        $hostName = config('bunnycdn-library.host_name', 'https://vz-5d4dcc09-d8f.b-cdn.net/');
        // Hostname'in https:// ile başladığından emin ol
        if (strpos($hostName, 'https://') !== 0) {
            $hostName = "https://{$hostName}";
        }
        // Hostname'in / ile bittiğinden emin ol
        if (substr($hostName, -1) !== '/') {
            $hostName .= '/';
        }

        $videoGuid = $this->video_guid ?? $this->attributes['video_guid'] ?? null;

        if (!$videoGuid && isset($this->attributes['video_url']) && !empty($this->attributes['video_url'])) {
            // Mevcut video_url'den video_guid'i çıkar
            $url = $this->attributes['video_url'];

            // URL'de domain ve GUID arasında / karakteri eksikse düzelt
            if (preg_match('/vz-[\w-]+\.b-cdn\.net([\w-]+)\/playlist\.m3u8/', $url, $matches)) {
                $videoGuid = $matches[1];
            } else {
                $parts = explode('/', $url);
                // URL formatı: https://vz-5d4dcc09-d8f.b-cdn.net/4e0d4709-ab41-4e43-a7f8-832e236482ef/playlist.m3u8
                if (count($parts) >= 4) {
                    $videoGuid = $parts[count($parts) - 2];
                }
            }
        }

        if ($videoGuid) {
            // HLS formatında URL döndür
            return "{$hostName}{$videoGuid}/playlist.m3u8";
        }

        // Eğer video_url varsa ve düzgün formatta değilse düzelt
        if (isset($this->attributes['video_url']) && !empty($this->attributes['video_url'])) {
            $url = $this->attributes['video_url'];
            if (strpos($url, 'https://') !== 0) {
                return "https://{$url}";
            }
        }

        return $this->attributes['video_url'] ?? null;
    }

    /**
     * Get the thumbnail URL based on the stored video_guid
     */
    public function getThumbnailUrlAttribute()
    {
        $fileName = $this->thumbnail_filename ?? 'thumbnail.jpg';
        return $this->video_guid ? app(BunnyCdnService::class)->getThumbnailUrl($this->video_guid, $fileName) : null;
    }

    /**
     * GraphQL şeması için video_likes_count accessor
     */
    public function getVideoLikesCountAttribute()
    {
        // MongoDB'nin sorgu ifadelerini doğru şekilde işleyebilmesi için
        // önce değerin bir sorgu ifadesi olup olmadığını kontrol ediyoruz
        if (isset($this->attributes['likes_count']) && is_object($this->attributes['likes_count'])) {
            return 0; // Sorgu ifadesi ise 0 döndür
        }
        return $this->attributes['likes_count'] ?? 0;
    }

    /**
     * GraphQL şeması için video_comments_count accessor
     */
    public function getVideoCommentsCountAttribute()
    {
        // MongoDB'nin sorgu ifadelerini doğru şekilde işleyebilmesi için
        // önce değerin bir sorgu ifadesi olup olmadığını kontrol ediyoruz
        if (isset($this->attributes['comments_count']) && is_object($this->attributes['comments_count'])) {
            return 0; // Sorgu ifadesi ise 0 döndür
        }
        return $this->attributes['comments_count'] ?? 0;
    }

    /**
     * Get embedded user data
     * This method provides access to the embedded user data
     * without needing to query the PostgreSQL database
     */
    public function getEmbeddedUser()
    {
        // Eğer user_data yoksa null döndür
        if (!isset($this->user_data) || empty($this->user_data)) {
            return null;
        }

        // Eğer user_data bir nesne değilse, nesneye dönüştür
        $userData = is_object($this->user_data) ? $this->user_data : (object)$this->user_data;

        // User nesnesi oluştur ve özellikleri ata
        $user = new \App\Models\User();

        // user_data'daki tüm özellikleri User nesnesine aktar
        foreach ($userData as $key => $value) {
            $user->$key = $value;
        }

        return $user;
    }

    /**
     * Get user_data attribute for GraphQL
     * This allows direct access to the embedded user data in GraphQL
     */
    public function getUserDataAttribute()
    {
        // Eğer user_data yoksa null döndür
        if (!isset($this->attributes['user_data']) || empty($this->attributes['user_data'])) {
            return null;
        }

        // Eğer user_data bir nesne değilse, nesneye dönüştür
        return is_object($this->attributes['user_data'])
            ? $this->attributes['user_data']
            : (object)$this->attributes['user_data'];
    }

    /**
     * Calculate the engagement score based on likes, comments, and views
     */
    public function calculateEngagementScore(): float
    {
        $likesWeight = 1.5;
        $commentsWeight = 2.0;
        $viewsWeight = 0.5;

        $likesCount = $this->video_likes()->count();
        $commentsCount = $this->video_comments()->count();
        $viewsCount = $this->views_count ?? 0;

        $score = ($likesCount * $likesWeight) +
            ($commentsCount * $commentsWeight) +
            ($viewsCount * $viewsWeight);

        return round($score, 2);
    }

    /**
     * Increment the view count for this video
     */
    public function incrementViewCount(): void
    {
        $this->increment('views_count');
    }

    /**
     * Scope a query to only include public videos
     */
    public function scopePublic($query)
    {
        return $query->where('is_private', false)
            ->where('is_banned', false);
    }

    /**
     * Scope a query to filter videos by tags
     */
    public function scopeWithTags($query, array $tags)
    {
        return $query->whereIn('tags', $tags);
    }

    /**
     * Scope a query to filter videos by team tags
     */
    public function scopeWithTeamTags($query, array $teamTags)
    {
        return $query->whereIn('team_tags', $teamTags);
    }

    /**
     * Scope a query to order videos by trending score
     */
    public function scopeTrending($query)
    {
        return $query->orderBy('trending_score', 'desc');
    }

    /**
     * Scope a query to order videos by engagement score
     */
    public function scopeByEngagement($query)
    {
        return $query->orderBy('engagement_score', 'desc');
    }

    /**
     * Scope a query to only include active (not soft-deleted) videos
     * This is in addition to the default scope provided by SoftDeletes
     */
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Check if the video is liked by a specific user
     *
     * @param string $userId
     * @return bool
     */
    public function isLikedByUser($userId)
    {
        // Check if there's a like record for this video and user
        return VideoLike::where('video_id', $this->id)
            ->where('user_id', $userId)
            ->exists();
    }

    public function getCreatedAt(): Attribute
    {
        return Attribute::get(
            fn() => $this->created_at->translatedFormat((new CommonHelper)->defaultDateTimeFormat())
        );
    }

    public function getUpdatedAt(): Attribute
    {
        return Attribute::get(
            fn() => $this->updated_at->translatedFormat((new CommonHelper)->defaultDateTimeFormat())
        );
    }

    public function getDeletedAt(): Attribute
    {
        return Attribute::get(
            fn() => $this->deleted_at ? $this->deleted_at->translatedFormat((new CommonHelper)->defaultDateTimeFormat()) : null
        );
    }

    /**
     * Videonun toplam izlenme süresi metriklerini hesaplar
     *
     * @return array
     */
    public function getTotalWatchTimeMetrics()
    {
        // VideoView koleksiyonundan toplam izlenme süresi metriklerini al
        $metrics = VideoView::raw(function ($collection) {
            return $collection->aggregate([
                [
                    '$match' => [
                        'video_id' => $this->id
                    ]
                ],
                [
                    '$group' => [
                        '_id' => null,
                        'total_duration_watched' => ['$sum' => '$duration_watched'],
                        'average_watch_time' => ['$avg' => '$duration_watched'],
                        'total_views' => ['$sum' => 1],
                        'completed_views' => [
                            '$sum' => [
                                '$cond' => [
                                    ['$eq' => ['$completed', true]],
                                    1,
                                    0
                                ]
                            ]
                        ],
                        'completion_rate' => [
                            '$avg' => [
                                '$cond' => [
                                    ['$eq' => ['$completed', true]],
                                    100,
                                    '$percentage_watched'
                                ]
                            ]
                        ]
                    ]
                ]
            ])->toArray();
        });

        if (empty($metrics)) {
            return [
                'total_duration_watched' => 0,
                'average_watch_time' => 0,
                'total_views' => 0,
                'completed_views' => 0,
                'completion_rate' => 0,
            ];
        }

        return $metrics[0];
    }

    /**
     * Video modeli için aksesör ve mutasörler
     */

    // Play count değeri için aksesör - null ise 0 döndür
    public function getPlayCountAttribute($value)
    {
        return $value ?? 0;
    }
}
