<?php

namespace App\Models\Challenge;

use App\Helpers\CommonHelper;
use App\Models\Agora\AgoraChannel;
use Mongodb\Laravel\Eloquent\Model;
use MongoDB\Laravel\Relations\HasMany;
use App\Models\Challenge\ChallengeTeam;
use MongoDB\Laravel\Relations\BelongsTo;
use App\Observers\Challenge\ChallengeObserver;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use App\Casts\DatetimeTz;
use App\Models\Traits\MongoTimestamps;

#[ObservedBy(ChallengeObserver::class)]
/**
 * @mixin IdeHelperChallenge
 */
class Challenge extends Model
{
    use MongoTimestamps;
    
    public const TYPE_1v1 = 1,
        TYPE_2v2 = 2;

    public static array $types = [
        self::TYPE_1v1 => '1v1',
        self::TYPE_2v2 => '2v2'
    ];

    public const STATUS_ACTIVE = 1,
        STATUS_CANCELLED = 2,
        STATUS_FINISHED = 3;

    public static array $statuses = [
        self::STATUS_ACTIVE => 'Aktif',
        self::STATUS_CANCELLED => 'İptal Edildi',
        self::STATUS_FINISHED => 'Bitmiş'
    ];

    public static array $statusColors = [
        self::STATUS_ACTIVE => 'success',
        self::STATUS_CANCELLED => 'danger',
        self::STATUS_FINISHED => 'secondary',
    ];

    protected $connection = 'mongodb';
    protected $collection = 'challenges';
    protected $fillable = [
        'agora_channel_id',
        'agora_channel_data',
        'type_id',
        'status_id',
        'started_at',
        'ended_at',

        'round_count', // round sayısı default 2
        'current_round', // mevcut round
        'round_duration', // her round için süre
        'max_coins', // her win için coin sayısı

        'total_coins_earned',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => DatetimeTz::class,
            'ended_at' => DatetimeTz::class,
        ];
    }

    /* start::Relations */
    public function agoraChannel(): BelongsTo
    {
        return $this->belongsTo(AgoraChannel::class, 'agora_channel_id', '_id');
    }

    public function teams(): HasMany
    {
        return $this->hasMany(ChallengeTeam::class, 'challenge_id', '_id');
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(ChallengeRound::class, 'challenge_id', '_id');
    }
    /* end::Relations */

    /* start::Attributes */
    public function getType(): Attribute
    {
        return Attribute::get(
            fn() => self::$types[$this->type_id] ?? null
        );
    }

    public function getStatus(): Attribute
    {
        return Attribute::get(
            fn() => self::$statuses[$this->status_id] ?? null
        );
    }

    public function getStatusColor(): Attribute
    {
        return Attribute::get(fn() => self::$statusColors[$this->status_id] ?? 'secondary');
    }

    public function getStartedAt(): Attribute
    {
        return Attribute::get(
            fn() => $this->started_at?->translatedFormat((new CommonHelper)->defaultDateTimeFormat(true))
        );
    }

    public function getEndedAt(): Attribute
    {
        return Attribute::get(
            fn() => $this->ended_at?->translatedFormat((new CommonHelper)->defaultDateTimeFormat(true))
        );
    }
    /* end::Attributes */
}
