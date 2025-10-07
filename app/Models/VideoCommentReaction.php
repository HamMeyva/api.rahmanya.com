<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Mongodb\Laravel\Eloquent\Model;
use App\Models\Traits\MongoTimestamps;

/**
 * @mixin IdeHelperVideoCommentReaction
 */
class VideoCommentReaction extends Model
{
    use HasFactory, MongoTimestamps;

    protected $connection = 'mongodb';
    protected $collection = 'video_comment_reactions';

    protected $fillable = [
        'user_id',
        'comment_id',
        'reaction_type' // 'like' veya 'dislike'
    ];

    public function index(array $keys, array $options = [])
    {
        // Access MongoDB connection directly
        $connection = $this->getConnection();
        $collection = $connection->getCollection($this->collection);
        $collection->createIndex($keys, $options);
    }

    protected static function boot()
    {
        parent::boot();
        
        // Create indexes for better performance
        $model = new static;
        $model->index(['comment_id' => 1, 'reaction_type' => 1]);
        $model->index(['user_id' => 1, 'comment_id' => 1]);
    }

    public function user()
    {
        return User::find($this->user_id);
    }

    public function comment()
    {
        return $this->belongsTo(VideoComment::class, 'comment_id');
    }
}
