<?php

namespace App\Models\Challenge;

use Mongodb\Laravel\Eloquent\Model;
use App\Casts\DatetimeTz;
use App\Models\Traits\MongoTimestamps;

/**
 * @mixin IdeHelperChallengeRound
 */
class ChallengeRound extends Model
{
    use MongoTimestamps;
    
    protected $connection = 'mongodb';
    protected $collection = 'challenge_rounds';

    protected $fillable = [
        'challenge_id',
        'round_number',
        'start_at',
        'end_at',

        'team_total_coins', //array
        'team_wins', //array
        'winner_team_no',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => DatetimeTz::class,
            'end_at' => DatetimeTz::class,
        ];
    }

    /* start::Relations */
    public function challenge()
    {
        return $this->belongsTo(Challenge::class, 'challenge_id');
    }
    /* end::Relations */
}
