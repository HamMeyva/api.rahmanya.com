<?php

namespace App\Models;

use App\Models\User;
use App\Helpers\CommonHelper;
use Mongodb\Laravel\Eloquent\Model;
use App\Models\VideoCommentReaction;
use App\Models\Traits\MongoTimestamps;
use App\Observers\VideoCommentObserver;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy(VideoCommentObserver::class)]
/**
 * @mixin IdeHelperVideoComment
 */
class VideoComment extends Model
{
    use HasFactory, MongoTimestamps;

    protected $connection = 'mongodb';
    protected $collection = 'video_comments';

    protected $fillable = [
        'user_id',
        'video_id',
        'parent_id',
        'comment',
        'original_comment',
        'has_banned_word',
        'is_deleted',
        'replies_count',
        'likes_count',
        'dislikes_count',
        'mentions'
    ];

    protected function casts(): array
    {
        return [
            'is_deleted' => 'boolean',
            'has_banned_word' => 'boolean',
        ];
    }

    public function reactions()
    {
        return $this->hasMany(VideoCommentReaction::class, 'comment_id');
    }

    public function index(array $keys, array $options = [])
    {
        $connection = $this->getConnection();
        $collection = $connection->getCollection($this->collection);
        $collection->createIndex($keys, $options);
    }


    public function user()
    {
        return User::find($this->user_id);
    }

    public function video()
    {
        return $this->belongsTo(Video::class);
    }

    public function parent()
    {
        return $this->belongsTo(VideoComment::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(VideoComment::class, 'parent_id');
    }

    public function getUserReaction($userId, $type = 'like')
    {
        return $this->reactions()
            ->where('user_id', $userId)
            ->where('reaction_type', $type)
            ->first();
    }

    public function getContentAttribute()
    {
        return $this->comment ?? '';
    }

    public function setContentAttribute($value)
    {
        $this->attributes['comment'] = $value;
    }

    public function getLikesCountAttribute()
    {
        return VideoCommentReaction::where('comment_id', $this->_id)
            ->where('reaction_type', 'like')
            ->count();
    }

    public function getDislikesCountAttribute()
    {
        return VideoCommentReaction::where('comment_id', $this->_id)
            ->where('reaction_type', 'dislike')
            ->count();
    }
    public function isLikedByUser($userId)
    {
        return $this->reactions()
            ->where('user_id', $userId)
            ->where('reaction_type', 'like')
            ->exists();
    }

    public function isDislikedByUser($userId)
    {
        return $this->reactions()
            ->where('user_id', $userId)
            ->where('reaction_type', 'dislike')
            ->exists();
    }

    public function getCreatedAt(): Attribute
    {
        return Attribute::get(
            fn() => $this->created_at->translatedFormat((new CommonHelper)->defaultDateTimeFormat())
        );
    }
}
