<?php

namespace App\Models;

use Illuminate\Support\Facades\Log;
use MongoDB\Laravel\Eloquent\Model;
use App\Casts\DatetimeTz;
use App\Models\Traits\MongoTimestamps;

/**
 * @mixin IdeHelperVideoMetrics
 */
class VideoMetrics extends Model
{
    use MongoTimestamps;
    
    protected $connection = 'mongodb';
    protected $collection = 'video_metrics';
    protected $primaryKey = '_id';

    protected $fillable = [
        'video_id',
        'likes_count',
        'comments_count',
        'views_count',
        'play_count',
        'completed_count',
        'is_played',
        'engagement_score',
        'trending_score',
        'user_interactions',
        'last_updated_at'
    ];

    protected function casts(): array
    {
        return [
            'likes_count' => 'integer',
            'comments_count' => 'integer',
            'views_count' => 'integer',
            'play_count' => 'integer',
            'completed_count' => 'integer',
            'is_played' => 'boolean',
            'engagement_score' => 'float',
            'trending_score' => 'float',
            'user_interactions' => 'array',
            'last_updated_at' => DatetimeTz::class
        ];
    }

    /**
     * Create MongoDB indexes for better query performance
     */
    public function index()
    {
        try {
            // Get the collection instance and create indexes
            $collection = $this->getCollection();
            $collection->createIndex(['video_id' => 1], ['unique' => true]);
            $collection->createIndex(['trending_score' => -1]);
            $collection->createIndex(['engagement_score' => -1]);
            $collection->createIndex(['last_updated_at' => -1]);
        } catch (\Exception $e) {
            Log::error('Error creating VideoMetrics indexes: ' . $e->getMessage());
        }
    }

    /**
     * Get the video that owns the metrics
     */
    public function video()
    {
        // Since this is a cross-database relationship (MongoDB to MongoDB),
        // we can use a direct find instead of Eloquent relationship
        if (!$this->video_id) {
            return null;
        }
        
        return Video::find($this->video_id);
    }

    /**
     * Update metrics from a video object
     * 
     * @param Video $video
     * @return bool
     */
    public static function updateFromVideo(Video $video)
    {
        try {
            $metrics = self::firstOrNew(['video_id' => $video->_id]);
            
            $metrics->likes_count = $video->likes_count ?? 0;
            $metrics->comments_count = $video->comments_count ?? 0;
            $metrics->views_count = $video->views_count ?? 0;
            $metrics->play_count = $video->play_count ?? 0;
            $metrics->completed_count = $video->completed_count ?? 0;
            $metrics->is_played = $video->is_played ?? false;
            $metrics->engagement_score = $video->engagement_score ?? 0;
            $metrics->trending_score = $video->trending_score ?? 0;
            $metrics->last_updated_at = now();
            
            return $metrics->save();
        } catch (\Exception $e) {
            Log::error('Error updating VideoMetrics: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Add a user interaction to the metrics
     * 
     * @param string $userId
     * @param string $type (like, comment, view)
     * @return bool
     */
    public function addUserInteraction(string $userId, string $type)
    {
        try {
            $interactions = $this->user_interactions ?? [];
            
            // Add the interaction if it doesn't exist
            $exists = false;
            foreach ($interactions as $interaction) {
                if ($interaction['user_id'] === $userId && $interaction['type'] === $type) {
                    $exists = true;
                    break;
                }
            }
            
            if (!$exists) {
                $interactions[] = [
                    'user_id' => $userId,
                    'type' => $type,
                    'created_at' => now()->toDateTimeString()
                ];
                
                $this->user_interactions = $interactions;
                $this->last_updated_at = now();
                return $this->save();
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Error adding user interaction to VideoMetrics: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get users who have interacted with this video
     * 
     * @param string $type Optional interaction type filter
     * @return array
     */
    public function getInteractingUsers(string $type = null)
    {
        $interactions = $this->user_interactions ?? [];
        $userIds = [];
        
        foreach ($interactions as $interaction) {
            if ($type === null || $interaction['type'] === $type) {
                $userIds[] = $interaction['user_id'];
            }
        }
        
        return array_unique($userIds);
    }
}
