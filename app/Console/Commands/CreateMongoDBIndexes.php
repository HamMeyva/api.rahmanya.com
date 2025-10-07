<?php

namespace App\Console\Commands;

use App\Models\Video;
use App\Models\VideoLike;
use App\Models\VideoComment;
use App\Models\VideoView;
use App\Models\VideoMetrics;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CreateMongoDBIndexes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mongodb:create-indexes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create MongoDB indexes for optimized performance';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Creating MongoDB indexes for optimized performance...');
        
        try {
            // Video collection indexes
            $this->info('Creating indexes for Video collection...');
            Video::raw(function ($collection) {
                // Basic indexes
                $collection->createIndex(['user_id' => 1], ['background' => true]);
                $collection->createIndex(['created_at' => -1], ['background' => true]);
                $collection->createIndex(['status' => 1], ['background' => true]);
                $collection->createIndex(['is_private' => 1], ['background' => true]);
                $collection->createIndex(['is_sport' => 1], ['background' => true]);
                
                // Performance indexes for feeds
                $collection->createIndex(['trending_score' => -1], ['background' => true]);
                $collection->createIndex(['engagement_score' => -1], ['background' => true]);
                $collection->createIndex(['views_count' => -1], ['background' => true]);
                $collection->createIndex(['play_count' => -1], ['background' => true]);
                $collection->createIndex(['likes_count' => -1], ['background' => true]);
                $collection->createIndex(['comments_count' => -1], ['background' => true]);
                
                // Compound indexes for common queries
                $collection->createIndex(['user_id' => 1, 'created_at' => -1], ['background' => true]);
                $collection->createIndex(['is_sport' => 1, 'trending_score' => -1], ['background' => true]);
                $collection->createIndex(['status' => 1, 'is_private' => 1, 'created_at' => -1], ['background' => true]);
                $collection->createIndex(['play_count' => -1, 'created_at' => -1], ['background' => true]);
                
                $this->info('Video collection indexes created successfully.');
            });
            
            // VideoLike collection indexes
            $this->info('Creating indexes for VideoLike collection...');
            VideoLike::raw(function ($collection) {
                $collection->createIndex(['video_id' => 1], ['background' => true]);
                $collection->createIndex(['user_id' => 1], ['background' => true]);
                $collection->createIndex(['created_at' => -1], ['background' => true]);
                $collection->createIndex(['video_id' => 1, 'user_id' => 1], ['unique' => true, 'background' => true]);
                
                $this->info('VideoLike collection indexes created successfully.');
            });
            
            // VideoComment collection indexes
            $this->info('Creating indexes for VideoComment collection...');
            VideoComment::raw(function ($collection) {
                $collection->createIndex(['video_id' => 1], ['background' => true]);
                $collection->createIndex(['user_id' => 1], ['background' => true]);
                $collection->createIndex(['created_at' => -1], ['background' => true]);
                $collection->createIndex(['parent_id' => 1], ['background' => true]);
                $collection->createIndex(['video_id' => 1, 'created_at' => -1], ['background' => true]);
                $collection->createIndex(['video_id' => 1, 'parent_id' => 1], ['background' => true]);
                
                $this->info('VideoComment collection indexes created successfully.');
            });
            
            // VideoView collection indexes
            $this->info('Creating indexes for VideoView collection...');
            VideoView::raw(function ($collection) {
                $collection->createIndex(['video_id' => 1], ['background' => true]);
                $collection->createIndex(['user_id' => 1], ['background' => true]);
                $collection->createIndex(['viewed_at' => -1], ['background' => true]);
                $collection->createIndex(['completed' => 1], ['background' => true]);
                $collection->createIndex(['user_id' => 1, 'viewed_at' => -1], ['background' => true]);
                
                $this->info('VideoView collection indexes created successfully.');
            });
            
            // VideoMetrics collection indexes
            $this->info('Creating indexes for VideoMetrics collection...');
            VideoMetrics::raw(function ($collection) {
                $collection->createIndex(['video_id' => 1], ['unique' => true, 'background' => true]);
                $collection->createIndex(['trending_score' => -1], ['background' => true]);
                $collection->createIndex(['engagement_score' => -1], ['background' => true]);
                $collection->createIndex(['last_updated_at' => -1], ['background' => true]);
                $collection->createIndex(['user_interactions.user_id' => 1], ['background' => true]);
                $collection->createIndex(['user_interactions.type' => 1], ['background' => true]);
                $collection->createIndex(['user_interactions.user_id' => 1, 'user_interactions.type' => 1], ['background' => true]);
                
                $this->info('VideoMetrics collection indexes created successfully.');
            });
            
            $this->info('All MongoDB indexes created successfully!');
            return 0;
        } catch (\Exception $e) {
            $this->error("Error creating MongoDB indexes: " . $e->getMessage());
            Log::error("Error in CreateMongoDBIndexes command: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
}
