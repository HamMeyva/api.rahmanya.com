<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use App\Casts\DatetimeTz;

/**
 * @mixin IdeHelperFollow
 */
class Follow extends Model
{
    use SoftDeletes;

    protected $table = 'follows';

    protected $fillable = [
        'follower_id',
        'followed_id',
        'status',
        'notify_on_accept',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => DatetimeTz::class,
            'updated_at' => DatetimeTz::class,
            'deleted_at' => DatetimeTz::class,
        ];
    }

    /**
     * Takip eden kullanıcı
     */
    public function follower(): BelongsTo
    {
        return $this->belongsTo(User::class, 'follower_id');
    }

    /**
     * Takip edilen kullanıcı
     */
    public function followed(): BelongsTo
    {
        return $this->belongsTo(User::class, 'followed_id');
    }
    
    /**
     * Custom method to ensure soft delete works consistently across environments
     * This is more reliable than the standard delete() method
     * 
     * @return bool|null Returns true if the follow was successfully deleted, false otherwise
     */
    public function softDeleteReliably(): bool
    {
        try {
            // Set the deleted_at timestamp explicitly
            $this->deleted_at = now();
            $result = $this->save();
            
            // Verify the timestamp was set
            if ($this->deleted_at === null) {
                \Log::error('Follow::softDeleteReliably - Failed to set deleted_at timestamp');
                return false;
            }
            
            \Log::info('Follow::softDeleteReliably - Successfully set deleted_at for follow ID: ' . $this->id);
            return $result;
        } catch (\Exception $e) {
            \Log::error('Follow::softDeleteReliably - Exception: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Custom method to restore a soft-deleted follow
     * 
     * @return bool Returns true if the follow was successfully restored, false otherwise
     */
    public function restoreReliably(): bool
    {
        try {
            // Set the deleted_at timestamp to null explicitly
            $this->deleted_at = null;
            $result = $this->save();
            
            // Verify the timestamp was cleared
            if ($this->deleted_at !== null) {
                \Log::error('Follow::restoreReliably - Failed to clear deleted_at timestamp');
                return false;
            }
            
            \Log::info('Follow::restoreReliably - Successfully restored follow ID: ' . $this->id);
            return $result;
        } catch (\Exception $e) {
            \Log::error('Follow::restoreReliably - Exception: ' . $e->getMessage());
            return false;
        }
    }
    
    public static function getFollowings(String $userId): Builder
    {
        return static::query()->where('follower_id', $userId);
    }
    
    public static function getFollowers(String $userId): Builder
    {
        return static::query()->where('followed_id', $userId);
    }


    public static function isFollowing(String $followerId, String $followedId): bool
    {
        return static::query()
            ->where('follower_id', $followerId)
            ->where('followed_id', $followedId)
            ->whereNull('deleted_at')
            ->exists();
    }
    
    /**
     * Get follower count for a user
     *
     * @param String $userId
     * @return int
     */
    public static function getFollowerCount(String $userId): int
    {
        return static::getFollowers($userId)->count();
    }
    
    /**
     * Get following count for a user
     *
     * @param String $userId
     * @return int
     */
    public static function getFollowingCount(String $userId): int
    {
        return static::getFollowings($userId)->count();
    }
    
    /**
     * PostgreSQL'deki kullanıcı istatistiklerini güncelle
     *
     * @param String $userId
     * @return void
     */
    public static function updateUserStats(String $userId): void
    {
        $followerCount = self::getFollowerCount($userId);
        $followingCount = self::getFollowingCount($userId);
        
        // PostgreSQL'de kullanıcı istatistiklerini güncelle veya oluştur
        UserStats::updateOrCreate(
            ['user_id' => $userId],
            [
                'follower_count' => $followerCount,
                'following_count' => $followingCount,
                'updated_at' => now(),
            ]
        );
    }
}
