<?php

namespace App\Models;

use App\Helpers\CommonHelper;
use Mongodb\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Casts\DatetimeTz;
use App\Models\Traits\MongoTimestamps;

/**
 * StoryLike model for tracking story likes
 *
 * @mixin IdeHelperStoryLike
 */
class StoryLike extends Model
{
    use MongoTimestamps;
    protected $connection = 'mongodb';
    protected $collection = 'story_likes';

    protected $fillable = [
        'story_id',
        'user_id',
        'user_data',
    ];

    protected function casts(): array
    {
        return [
            
        ];
    }

    public function story()
    {
        return Story::find($this->story_id);
    }

    public function user()
    {
        return User::find($this->user_id);
    }

    public function getUserDataAttribute()
    {
        if (!isset($this->attributes['user_data']) || empty($this->attributes['user_data'])) {
            return null;
        }
        
        return is_object($this->attributes['user_data']) 
            ? $this->attributes['user_data'] 
            : (object)$this->attributes['user_data'];
    }

    public static function createIndexes()
    {
        self::raw(function($collection) {
            $collection->createIndex(['story_id' => 1]);
            $collection->createIndex(['user_id' => 1]);
            $collection->createIndex(['created_at' => -1]);
            // Unique index to prevent duplicate likes
            $collection->createIndex(['story_id' => 1, 'user_id' => 1], ['unique' => true]);
        });
    }
    
    public function getCreatedAt(): Attribute
    {
        return Attribute::get(
            fn() => $this->created_at?->translatedFormat((new CommonHelper)->defaultDateTimeFormat(true))
        );
    }
}
