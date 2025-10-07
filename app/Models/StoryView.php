<?php

namespace App\Models;

use App\Models\Story;
use App\Helpers\CommonHelper;
use Mongodb\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Casts\DatetimeTz;
use App\Models\Traits\MongoTimestamps;

/**
 * StoryView model for tracking story views
 *
 * @mixin IdeHelperStoryView
 */
class StoryView extends Model
{
    use MongoTimestamps;
    protected $connection = 'mongodb';
    protected $collection = 'story_views';

    protected $fillable = [
        'story_id',
        'user_id',
        'user_data',
        'view_duration',
        'completed',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'completed' => 'boolean',
        ];
    }


    public function story()
    {
        $storyId = $this->story_id;
        if (!$storyId) {
            return null;
        }
        return Story::find($storyId);
    }

    public function user()
    {
        $userId = $this->user_id;
        if (!$userId) {
            return null;
        }
        
        // Performans için önce gömülü veriyi kullan
        if ($this->user_data) {
            return $this->getEmbeddedUser();
        }
        
        return \App\Models\User::find($userId);
    }


    public function getEmbeddedUser()
    {
        if (!$this->user_data) {
            return null;
        }

        $user = new User();
        foreach ((array)$this->user_data as $key => $value) {
            $user->$key = $value;
        }
        return $user;
    }

    public static function createIndexes()
    {
        self::raw(function($collection) {
            $collection->createIndex(['story_id' => 1]);
            $collection->createIndex(['user_id' => 1]);
            $collection->createIndex(['created_at' => -1]);
            // Unique index to count unique views
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
