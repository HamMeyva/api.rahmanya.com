<?php

namespace App\Models;

use App\Helpers\CommonHelper;
use Mongodb\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Traits\MongoTimestamps;
use App\Observers\VideoLikeObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy(VideoLikeObserver::class)]
/**
 * @mixin IdeHelperVideoLike
 */
class VideoLike extends Model
{
    use HasFactory, MongoTimestamps;

    protected $connection = 'mongodb';
    protected $collection = 'video_likes';

    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'video_id',
    ];

    protected function casts(): array
    {
        return [];
    }

    protected $append = [
        'get_created_at'
    ];

    public function user(): User|null
    {
        return User::where('id', $this->user_id)->first();
    }

    public function video()
    {
        return $this->belongsTo(Video::class);
    }

    public function getCreatedAt(): Attribute
    {
        return Attribute::get(
            fn() => $this->created_at->translatedFormat((new CommonHelper)->defaultDateTimeFormat())
        );
    }
}
