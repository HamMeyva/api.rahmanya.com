<?php

namespace App\Models;

use App\Models\User;
use Mongodb\Laravel\Eloquent\Model;
use App\Casts\DatetimeTz;
use App\Models\Traits\MongoTimestamps;

/**
 * @mixin IdeHelperNotification
 */
class Notification extends Model
{
    use MongoTimestamps;
    protected $connection = 'mongodb';
    protected $collection = 'notifications';

    protected $fillable = [
        'type',
        'user_id',
        'notification_type',
        'title',
        'body',
        'data',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => DatetimeTz::class,
        ];
    }

    public function user()
    {
        return User::find($this->user_id);
    }


    public function markAsRead()
    {
        if ($this->read_at === null) {
            $this->forceFill(['read_at' => $this->freshTimestamp()])->save();
        }
    }

    public function markAsUnread()
    {
        if ($this->read_at !== null) {
            $this->forceFill(['read_at' => null])->save();
        }
    }

    public function read()
    {
        return $this->read_at !== null;
    }

    public function unread()
    {
        return $this->read_at === null;
    }
}
