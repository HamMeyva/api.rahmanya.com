<?php

namespace App\Models;

use App\Models\User;
use App\Observers\UserSessionLogObserver;
use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use App\Casts\DatetimeTz;
use App\Models\Traits\MongoTimestamps;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Helpers\CommonHelper;
#[ObservedBy(UserSessionLogObserver::class)]
/**
 * @mixin IdeHelperUserSessionLog
 */
class UserSessionLog extends Model
{
    use MongoTimestamps;
    protected $connection = 'mongodb';
    protected $collection = 'user_session_logs';
    protected $fillable = [
        'user_id',
        'user_data',
        'start_at',
        'end_at',
        'duration',
    ];
    protected function casts(): array
    {
        return [
            'start_at' => DatetimeTz::class,
            'end_at' => DatetimeTz::class,
        ];
    }

    public function user()
    {
        $userId = $this->user_id;
        if (!$userId) {
            return null;
        }
        return User::find($userId);
    }


    public function getStartAt(): Attribute
    {
        return Attribute::get(
            fn() => $this->start_at ? $this->start_at->translatedFormat((new CommonHelper)->defaultDateTimeFormat()) : null
        );
    }

    public function getEndAt(): Attribute
    {
        return Attribute::get(
            fn() => $this->end_at ? $this->end_at->translatedFormat((new CommonHelper)->defaultDateTimeFormat()) : null
        );
    }
}
