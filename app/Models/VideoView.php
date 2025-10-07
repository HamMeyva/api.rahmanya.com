<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Mongodb\Laravel\Eloquent\Model;
use App\Casts\DatetimeTz;
use App\Models\Traits\MongoTimestamps;
use App\Observers\VideoViewObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy(VideoViewObserver::class)]
/**
 * @mixin IdeHelperVideoView
 */
class VideoView extends Model
{
    use HasFactory, MongoTimestamps;

    protected $connection = 'mongodb';
    protected $collection = 'video_views';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'video_id',
        'viewed_at',
        'duration_watched',
        'completed',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'viewed_at' => DatetimeTz::class,
            'completed' => 'boolean',
        ];
    }

    /**
     * Get the user that viewed the video.
     */
    public function user()
    {
        return User::find($this->user_id);
    }

    /**
     * Get the video that was viewed.
     */
    public function video()
    {
        return $this->belongsTo(Video::class);
    }
}
